<?php

use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Jobs\RecordEventLogJob;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Support\Str;

test('job creates row when event logs are enabled', function () {
    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.connection', 'testing');

    $payload = [
        'uuid' => (string) Str::uuid(),
        'type' => EventLogTypeEnum::HTTP,
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ];

    (new RecordEventLogJob($payload))->handle();

    expect(EventLog::count())->toBe(1);
});

test('job does nothing when event logs are disabled', function () {
    config()->set('laravel-event-logs.enabled', false);
    config()->set('laravel-event-logs.connection', 'testing');

    $payload = [
        'uuid' => (string) Str::uuid(),
        'type' => EventLogTypeEnum::HTTP,
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ];

    (new RecordEventLogJob($payload))->handle();

    expect(EventLog::count())->toBe(0);
});

test('job does nothing when connection is not configured', function () {
    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.connection', null);

    $payload = [
        'uuid' => (string) Str::uuid(),
        'type' => EventLogTypeEnum::HTTP,
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ];

    (new RecordEventLogJob($payload))->handle();

    expect(EventLog::count())->toBe(0);
});
