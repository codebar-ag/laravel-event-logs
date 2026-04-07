<?php

namespace CodebarAg\LaravelEventLogs\Support;

use Illuminate\Support\Facades\Context;

final class ContextExporter
{
    /**
     * @return array<string, mixed>
     */
    public static function forPersistence(): array
    {
        if (! (bool) config('laravel-event-logs.context.enabled', true)) {
            return [];
        }

        $all = Context::all();
        if (! is_array($all) || $all === []) {
            return [];
        }

        $allowKeys = config('laravel-event-logs.context.allow_keys');
        if (is_array($allowKeys) && $allowKeys !== []) {
            /** @var array<int, string> $filtered */
            $filtered = array_values(array_filter($allowKeys, static fn ($k): bool => is_string($k)));
            if ($filtered !== []) {
                $all = array_intersect_key($all, array_flip($filtered));
            }
        }

        $maxKeys = config('laravel-event-logs.context.max_keys');
        if (is_int($maxKeys) && $maxKeys > 0 && count($all) > $maxKeys) {
            $all = array_slice($all, 0, $maxKeys, true);
        }

        $maxBytes = config('laravel-event-logs.context.max_json_bytes');
        if (! is_int($maxBytes) || $maxBytes <= 0) {
            return $all;
        }

        $encoded = json_encode($all);
        if ($encoded !== false && strlen($encoded) <= $maxBytes) {
            return $all;
        }

        while ($all !== []) {
            array_pop($all);
            $encoded = json_encode($all);
            if ($encoded !== false && strlen($encoded) <= $maxBytes) {
                return $all;
            }
        }

        return [];
    }
}
