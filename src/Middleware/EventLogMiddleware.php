<?php

namespace CodebarAg\LaravelEventLogs\Middleware;

use Closure;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Support\ContextExporter;
use CodebarAg\LaravelEventLogs\Support\EventLogRecorder;
use CodebarAg\LaravelEventLogs\Support\RouteExclusion;
use CodebarAg\LaravelEventLogs\Support\SanitizeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class EventLogMiddleware
{
    private const PENDING_ATTRIBUTE = 'laravel_event_logs.pending_http';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! EventLog::isEnabled()) {
            return $next($request);
        }

        $user = $this->resolveUser($request);

        $excludeRoutesConfig = config('laravel-event-logs.exclude_routes', []);
        $excludeRoutes = is_array($excludeRoutesConfig)
            ? array_values(array_filter($excludeRoutesConfig, static fn ($v): bool => is_string($v)))
            : [];
        $route = $request->route();
        $currentRouteNameRaw = $route ? $route->getName() : null;
        $currentRouteName = is_string($currentRouteNameRaw) ? $currentRouteNameRaw : null;

        $matchModeRaw = config('laravel-event-logs.exclude_routes_match', 'exact');
        $matchMode = is_string($matchModeRaw) && in_array($matchModeRaw, ['exact', 'wildcard', 'auto'], true)
            ? $matchModeRaw
            : 'exact';

        if (RouteExclusion::shouldExclude($currentRouteName, $excludeRoutes, $matchMode)) {
            return $next($request);
        }

        $headersExcludeConfig = config('laravel-event-logs.sanitize.request_headers_exclude', []);
        $dataExcludeConfig = config('laravel-event-logs.sanitize.request_data_exclude', []);

        /** @var array<int, string> $requestHeadersToRemove */
        $requestHeadersToRemove = is_array($headersExcludeConfig)
            ? array_values(array_filter($headersExcludeConfig, static fn ($v): bool => is_string($v)))
            : [];
        /** @var array<int, string> $requestDataToRemove */
        $requestDataToRemove = is_array($dataExcludeConfig)
            ? array_values(array_filter($dataExcludeConfig, static fn ($v): bool => is_string($v)))
            : [];

        /** @var object{id: int|string}|null $user */
        $request->attributes->set(self::PENDING_ATTRIBUTE, [
            'started_at' => microtime(true),
            'payload' => [
                'type' => EventLogTypeEnum::HTTP,
                'user_type' => $user ? get_class($user) : null,
                'user_id' => $user !== null ? $user->id : null,
                'request_ip' => $request->ip(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_route' => $currentRouteName,
                'request_headers' => SanitizeHelper::removeKeys($request->headers->all(), $requestHeadersToRemove),
                'request_data' => SanitizeHelper::removeKeys($request->all(), $requestDataToRemove),
                'context' => ContextExporter::forPersistence(),
            ],
        ]);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! EventLog::isEnabled()) {
            return;
        }

        $pending = $request->attributes->get(self::PENDING_ATTRIBUTE);
        if (! is_array($pending)) {
            return;
        }

        $startedAt = Arr::get($pending, 'started_at');
        $payloadRaw = Arr::get($pending, 'payload');
        if (! is_array($payloadRaw)) {
            return;
        }

        if (! is_float($startedAt) && ! is_int($startedAt)) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $payloadRaw;

        $payload['response_status'] = $response->getStatusCode();
        $payload['duration_ms'] = (int) round((microtime(true) - (float) $startedAt) * 1000);

        EventLogRecorder::record($payload);
    }

    /**
     * Resolve authenticated user from request.
     *
     * @return object{id: int|string}|null
     */
    protected function resolveUser(Request $request): ?object
    {
        $configuredGuards = config('laravel-event-logs.user_resolution.guards');
        $scanAll = (bool) config('laravel-event-logs.user_resolution.scan_all_guards', false);

        if (is_array($configuredGuards) && $configuredGuards !== []) {
            /** @var array<int, string> $configuredGuards */
            $configuredGuards = array_values(array_filter($configuredGuards, static fn ($g): bool => is_string($g)));
            foreach ($configuredGuards as $guard) {
                $user = $request->user($guard);
                if ($user !== null) {
                    return $user;
                }
            }

            return null;
        }

        $defaultGuard = config('auth.defaults.guard');
        if (is_string($defaultGuard) && $defaultGuard !== '') {
            $user = $request->user($defaultGuard);
            if ($user !== null) {
                return $user;
            }
        }

        $fallback = $request->user();
        if ($fallback !== null) {
            return $fallback;
        }

        if (! $scanAll) {
            return null;
        }

        $guardsConfig = config('auth.guards', []);
        /** @var array<string, mixed> $guardsConfig */
        $guards = array_keys($guardsConfig);

        /** @var array<int, string> $guards */
        return Collection::make($guards)
            ->map(fn (string $guard) => $request->user($guard))
            ->filter()
            ->first();
    }
}
