<?php

namespace CodebarAg\LaravelEventLogs\Support;

class SanitizeHelper
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keysToRemove
     * @return array<string, mixed>
     */
    public static function removeKeys(array $data = [], array $keysToRemove = []): array
    {
        $lookup = array_fill_keys(array_map('strtolower', $keysToRemove), true);

        return array_filter(
            $data,
            fn ($v, $k) => ! isset($lookup[strtolower($k)]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Like {@see removeKeys()}, but also strips matching keys from nested arrays
     * (e.g. `user[password]` form fields or nested JSON bodies).
     *
     * @param  array<array-key, mixed>  $data
     * @param  array<int, string>  $keysToRemove
     * @return array<array-key, mixed>
     */
    public static function removeKeysRecursive(array $data = [], array $keysToRemove = []): array
    {
        if ($keysToRemove === []) {
            return $data;
        }

        $lookup = array_fill_keys(array_map('strtolower', $keysToRemove), true);

        return self::filterRecursive($data, $lookup);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  array<string, true>  $lookup
     * @return array<array-key, mixed>
     */
    private static function filterRecursive(array $data, array $lookup): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (isset($lookup[strtolower((string) $key)])) {
                continue;
            }

            $result[$key] = is_array($value) ? self::filterRecursive($value, $lookup) : $value;
        }

        return $result;
    }
}
