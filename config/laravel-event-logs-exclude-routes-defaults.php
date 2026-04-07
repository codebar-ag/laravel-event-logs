<?php

/**
 * Default route names skipped by HTTP event logging (Nova, Livewire, etc.).
 * Override via published config/laravel-event-logs.php key `exclude_routes`.
 *
 * @return array<int, string>
 */
return [
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
];
