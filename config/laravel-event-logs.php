<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Logs Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('EVENT_LOGS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Persistence
    |--------------------------------------------------------------------------
    |
    | persist_mode: sync (default) writes immediately; queued dispatches RecordEventLogJob.
    |
    */

    'persist_mode' => env('EVENT_LOGS_PERSIST_MODE', 'sync'),

    'queue' => [
        'connection' => env('EVENT_LOGS_QUEUE_CONNECTION'),
        'queue' => env('EVENT_LOGS_QUEUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify the database connection for event_logs (recommended: a dedicated connection).
    | Required when enabled is true (non-empty string).
    |
    */

    'connection' => env('EVENT_LOGS_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Sanitization Options
    |--------------------------------------------------------------------------
    */

    'sanitize' => [
        'request_headers_exclude' => [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ],
        'request_data_exclude' => [
            'password',
            'password_confirmation',
            '_token',
            'token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Context persistence (Laravel Context facade)
    |--------------------------------------------------------------------------
    |
    | Control which context keys are stored on event_logs rows and cap size.
    |
    */

    'context' => [
        'enabled' => env('EVENT_LOGS_CONTEXT_ENABLED', true),
        'allow_keys' => (($ck = env('EVENT_LOGS_CONTEXT_ALLOW_KEYS')) !== null && $ck !== '')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $ck))))
            : [],
        'max_keys' => (($mk = env('EVENT_LOGS_CONTEXT_MAX_KEYS')) !== null && $mk !== '')
            ? (int) $mk
            : null,
        'max_json_bytes' => (($mb = env('EVENT_LOGS_CONTEXT_MAX_JSON_BYTES')) !== null && $mb !== '')
            ? (int) $mb
            : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | User resolution (HTTP middleware)
    |--------------------------------------------------------------------------
    |
    | guards: ordered list of guard names to try (null = use auth.defaults.guard only,
    | then optional scan_all_guards). scan_all_guards: iterate every key in auth.guards.
    |
    */

    'user_resolution' => [
        'guards' => env('EVENT_LOGS_USER_GUARDS')
            ? array_values(array_filter(array_map('trim', explode(',', (string) env('EVENT_LOGS_USER_GUARDS')))))
            : null,
        'scan_all_guards' => env('EVENT_LOGS_SCAN_ALL_GUARDS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Exclusion
    |--------------------------------------------------------------------------
    |
    | exclude_routes_match: auto (default) uses * globs (Str::is) and trailing . as route-name prefix
    | (e.g. nova. matches nova.pages.home). Use exact for full-name matches only, or wildcard to apply
    | Str::is to every pattern.
    |
    */

    'exclude_routes_match' => env('EVENT_LOGS_EXCLUDE_ROUTES_MATCH', 'auto'),

    'exclude_routes' => require __DIR__.'/laravel-event-logs-exclude-routes-defaults.php',
];
