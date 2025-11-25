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
            'endpoint' => env('AZURE_EVENT_HUB_ENDPOINT'),
            'path' => env('AZURE_EVENT_HUB_PATH'),
            'primary_key' => env('AZURE_EVENT_HUB_PRIMARY_KEY'),
            'policy_name' => env('AZURE_EVENT_HUB_POLICY'),
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
    | Route Exclusion
    |--------------------------------------------------------------------------
    */

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
