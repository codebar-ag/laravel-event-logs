<?php

namespace CodebarAg\LaravelEventLogs\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class RequestUserResolver
{
    /**
     * @return object{id: int|string}|null
     */
    public function resolve(Request $request): ?object
    {
        $configuredGuards = config('laravel-event-logs.user_resolution.guards');
        $scanAll = (bool) config('laravel-event-logs.user_resolution.scan_all_guards', false);

        if (is_array($configuredGuards) && $configuredGuards !== []) {
            /** @var array<int, string> $guards */
            $guards = array_values(array_filter($configuredGuards, static fn ($g): bool => is_string($g)));
            foreach ($guards as $guard) {
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
        $guardNames = array_keys($guardsConfig);

        /** @var array<int, string> $guardNames */
        return Collection::make($guardNames)
            ->map(fn (string $guard) => $request->user($guard))
            ->filter()
            ->first();
    }
}
