<?php

use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Observers\EventLogObserver;

test('observer sets uuid when creating event log without uuid', function () {
    $eventLog = new EventLog;
    $eventLog->type = EventLogTypeEnum::HTTP;

    $observer = new EventLogObserver;
    $observer->creating($eventLog);

    expect($eventLog->uuid)->not->toBeNull();
    expect($eventLog->uuid)->toBeString();
});

test('observer preserves existing uuid when creating event log with uuid', function () {
    $existingUuid = 'existing-uuid-123';
    $eventLog = new EventLog;
    $eventLog->uuid = $existingUuid;
    $eventLog->type = EventLogTypeEnum::HTTP;

    $observer = new EventLogObserver;
    $observer->creating($eventLog);

    expect($eventLog->uuid)->toBe($existingUuid);
});

test('observer sets default type when creating event log without type', function () {
    $eventLog = new EventLog;
    $eventLog->uuid = 'test-uuid';

    $observer = new EventLogObserver;
    $observer->creating($eventLog);

    expect($eventLog->type)->toBe(EventLogTypeEnum::DEFAULT);
});

test('observer preserves existing type when creating event log with type', function () {
    $eventLog = new EventLog;
    $eventLog->uuid = 'test-uuid';
    $eventLog->type = EventLogTypeEnum::MODEL;

    $observer = new EventLogObserver;
    $observer->creating($eventLog);

    expect($eventLog->type)->toBe(EventLogTypeEnum::MODEL);
});

test('observer automatically sets uuid and type when creating event log', function () {
    $eventLog = EventLog::create([
        'request_method' => 'GET',
        'request_url' => 'https://example.com',
    ]);

    expect($eventLog->uuid)->not->toBeNull();
    expect($eventLog->type)->toBe(EventLogTypeEnum::DEFAULT);
});
