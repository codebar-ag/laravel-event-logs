<?php

use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Jobs\RecordEventLogJob;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Support\EventLogRecorder;
use Illuminate\Support\Facades\Bus;

test('recorder creates row when persist mode is sync', function () {
    config()->set('laravel-event-logs.persist_mode', 'sync');

    EventLogRecorder::record([
        'type' => EventLogTypeEnum::HTTP,
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ]);

    expect(EventLog::count())->toBe(1);
});

test('recorder dispatches job when persist mode is queued', function () {
    Bus::fake();

    config()->set('laravel-event-logs.persist_mode', 'queued');

    EventLogRecorder::record([
        'type' => EventLogTypeEnum::HTTP,
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ]);

    Bus::assertDispatched(RecordEventLogJob::class);
    expect(EventLog::count())->toBe(0);
});
