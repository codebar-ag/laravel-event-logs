<?php

/**
 * Default route names skipped by HTTP event logging (Nova, Livewire, livewire-filepond, etc.).
 *
 * Patterns use prefix matching when `exclude_routes_match` is `auto` (the package default):
 * a trailing `.` means "any route whose name starts with this string" (e.g. `nova.` matches
 * `nova.pages.home`). Use `exact` if you only list full route names; use `wildcard` to apply
 * `Str::is()` to every pattern (e.g. `nova.*`).
 *
 * Override via published `config/laravel-event-logs.php` key `exclude_routes`.
 *
 * @return array<int, string>
 */
return [
    'livewire-filepond.',
    'livewire.',
    'nova.',
];
