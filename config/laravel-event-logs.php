<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Logs Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('EVENT_LOGS_ENABLED', false),

    /*
    | When true, EventLog::toArray() returns the Azure export shape (legacy).
    | Prefer calling toProviderPayload() for exports; set false for standard Eloquent arrays.
    */

    'legacy_to_array_provider_payload' => env('EVENT_LOGS_LEGACY_TO_ARRAY', true),

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
    | Default outbound transport (extensible)
    |--------------------------------------------------------------------------
    */

    'default_transport' => env('EVENT_LOGS_DEFAULT_TRANSPORT', 'azure_event_hub'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify a custom database connection for event logs.
    | Set to null to use the default database connection.
    |
    */

    'connection' => env('EVENT_LOGS_CONNECTION', null),

    /*
    | Provider-specific configuration
    | Add other providers later; keep Azure as the initial provider
    */
    'providers' => [
        'azure_event_hub' => [
            'driver' => 'azure_event_hub',
            'endpoint' => env('AZURE_EVENT_HUB_ENDPOINT'),
            'path' => env('AZURE_EVENT_HUB_PATH'),
            'primary_key' => env('AZURE_EVENT_HUB_PRIMARY_KEY'),
            'policy_name' => env('AZURE_EVENT_HUB_POLICY'),
            'cache_sas_token' => env('AZURE_EVENT_HUB_CACHE_SAS_TOKEN', true),
            'token_cache_buffer_seconds' => env('AZURE_EVENT_HUB_TOKEN_CACHE_BUFFER') !== null && env('AZURE_EVENT_HUB_TOKEN_CACHE_BUFFER') !== ''
                ? (int) env('AZURE_EVENT_HUB_TOKEN_CACHE_BUFFER')
                : 60,
            'sas_ttl_seconds' => env('AZURE_EVENT_HUB_SAS_TTL') !== null && env('AZURE_EVENT_HUB_SAS_TTL') !== ''
                ? (int) env('AZURE_EVENT_HUB_SAS_TTL')
                : 7200,
        ],
    ],

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
    | exclude_routes_match: exact (default, backward compatible), wildcard (Str::is per pattern),
    | auto (* glob or trailing . prefix), e.g. nova.api. matches nova.api.foo.
    |
    */

    'exclude_routes_match' => env('EVENT_LOGS_EXCLUDE_ROUTES_MATCH', 'exact'),

    'exclude_routes' => [
        'livewire-filepond.scripts',
        'livewire-filepond.styles',
        'livewire.preview-file',
        'livewire.update',
        'livewire.upload-file',

        'nova.api.',
        'nova.asset.',
        'nova.pages.home',
        'nova.pages.403',
        'nova.pages.404',
        'nova.pages.dashboard',
        'nova.pages.dashboard.custom',
        'nova.pages.login',
        'nova.pages.index',
        'nova.pages.lens',
        'nova.pages.create',
        'nova.pages.detail',
        'nova.pages.attach',
        'nova.pages.edit',
        'nova.pages.edit-attached',
        'nova.pages.replicate',
        'nova.pages.user-security',
        'nova.pages.password.verify',
        'nova.password.confirm',
        'nova.password.confirmation',
        'nova.two-factor.login',
        'nova.api.start-nova-impersonation',
        'nova.api.stop-nova-impersonation',
    ],
];
