<?php

namespace CodebarAg\LaravelEventLogs\Support;

use Illuminate\Support\Str;

final class RouteExclusion
{
    /**
     * @param  array<int, string>  $patterns
     */
    public static function shouldExclude(?string $routeName, array $patterns, string $matchMode = 'exact'): bool
    {
        if ($routeName === null || $routeName === '' || $patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (self::patternMatches($routeName, $pattern, $matchMode)) {
                return true;
            }
        }

        return false;
    }

    public static function patternMatches(string $routeName, string $pattern, string $matchMode): bool
    {
        return match ($matchMode) {
            'wildcard' => Str::is($pattern, $routeName),
            'auto' => self::matchesAuto($routeName, $pattern),
            default => $routeName === $pattern,
        };
    }

    private static function matchesAuto(string $routeName, string $pattern): bool
    {
        if (str_contains($pattern, '*')) {
            return Str::is($pattern, $routeName);
        }

        if (str_ends_with($pattern, '.')) {
            return str_starts_with($routeName, $pattern);
        }

        return $routeName === $pattern;
    }
}
