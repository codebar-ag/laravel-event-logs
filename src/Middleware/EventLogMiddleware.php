<?php

namespace CodebarAg\LaravelEventLogs\Middleware;

use Closure;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Support\SanitizeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class EventLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

        if ($currentRouteName !== null && in_array($currentRouteName, $excludeRoutes, true)) {
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
        EventLog::create([
            'type' => EventLogTypeEnum::HTTP,
            'user_type' => $user ? get_class($user) : null,
            'user_id' => $user !== null ? $user->id : null,
            'request_ip' => $request->ip(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_route' => $currentRouteName,
            'request_headers' => SanitizeHelper::removeKeys($request->headers->all(), $requestHeadersToRemove),
            'request_data' => SanitizeHelper::removeKeys($request->all(), $requestDataToRemove),
            'context' => Context::all(),
        ]);

        return $next($request);
    }

    /**
     * Resolve authenticated user from request.
     * Checks all configured guards dynamically and guards from route middleware.
     *
     * @return object{id: int|string}|null
     */
    protected function resolveUser(Request $request): ?object
    {
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
