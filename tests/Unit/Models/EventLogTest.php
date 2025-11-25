<?php

use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;

test('event log can be created with all attributes', function () {
    $eventLog = EventLog::create([
        'uuid' => 'test-uuid-123',
        'type' => EventLogTypeEnum::MODEL,
        'subject_type' => 'App\Models\User',
        'subject_id' => 1,
        'user_type' => 'App\Models\User',
        'user_id' => 1,
        'request_route' => 'api.users.store',
        'request_method' => 'POST',
        'request_url' => 'https://example.com/api/users',
        'request_ip' => '127.0.0.1',
        'request_headers' => ['Content-Type' => 'application/json'],
        'request_data' => ['name' => 'John Doe'],
        'event' => EventLogEventEnum::CREATED,
        'event_data' => ['id' => 1],
        'context' => ['tenant_id' => 1],
    ]);

    expect($eventLog)
        ->uuid->toBe('test-uuid-123')
        ->type->toBe(EventLogTypeEnum::MODEL)
        ->subject_type->toBe('App\Models\User')
        ->subject_id->toBe(1)
        ->user_type->toBe('App\Models\User')
        ->user_id->toBe(1)
        ->request_route->toBe('api.users.store')
        ->request_method->toBe('POST')
        ->request_url->toBe('https://example.com/api/users')
        ->request_ip->toBe('127.0.0.1')
        ->request_headers->toBe(['Content-Type' => 'application/json'])
        ->request_data->toBe(['name' => 'John Doe'])
        ->event->toBe(EventLogEventEnum::CREATED)
        ->event_data->toBe(['id' => 1])
        ->context->toBe(['tenant_id' => 1]);
});

test('event log casts arrays correctly', function () {
    $eventLog = EventLog::create([
        'uuid' => 'test-uuid-456',
        'type' => EventLogTypeEnum::MODEL,
        'request_headers' => ['Authorization' => 'Bearer token'],
        'request_data' => ['email' => 'test@example.com'],
        'event_data' => ['changes' => ['name' => 'Jane Doe']],
        'context' => ['session_id' => 'abc123'],
    ]);

    expect($eventLog->request_headers)->toBe(['Authorization' => 'Bearer token']);
    expect($eventLog->request_data)->toBe(['email' => 'test@example.com']);
    expect($eventLog->event_data)->toBe(['changes' => ['name' => 'Jane Doe']]);
    expect($eventLog->context)->toBe(['session_id' => 'abc123']);
});

test('event log casts enums correctly', function () {
    $eventLog = EventLog::create([
        'uuid' => 'test-uuid-789',
        'type' => EventLogTypeEnum::MODEL,
        'event' => EventLogEventEnum::DELETED,
    ]);

    expect($eventLog->type)->toBe(EventLogTypeEnum::MODEL);
    expect($eventLog->event)->toBe(EventLogEventEnum::DELETED);
});

test('scope unsynced returns only unsynced logs', function () {
    EventLog::create([
        'uuid' => 'synced-1',
        'type' => EventLogTypeEnum::MODEL,
        'synced_at' => now(),
    ]);

    EventLog::create([
        'uuid' => 'unsynced-1',
        'type' => EventLogTypeEnum::MODEL,
        'synced_at' => null,
    ]);

    EventLog::create([
        'uuid' => 'unsynced-2',
        'type' => EventLogTypeEnum::MODEL,
        'synced_at' => null,
    ]);

    $unsyncedLogs = EventLog::unsynced()->get();

    expect($unsyncedLogs)->toHaveCount(2);
    expect($unsyncedLogs->pluck('uuid')->toArray())->toContain('unsynced-1', 'unsynced-2');
});

test('event log has correct table name', function () {
    $eventLog = new EventLog;
    expect($eventLog->getTable())->toBe('event_logs');
});

test('event log has correct fillable attributes', function () {
    $fillable = [
        'uuid',
        'type',
        'subject_type',
        'subject_id',
        'user_type',
        'user_id',
        'request_route',
        'request_method',
        'request_url',
        'request_ip',
        'request_headers',
        'request_data',
        'event',
        'event_data',
        'context',
        'synced_at',
        'sync_failed_at',
    ];

    expect((new EventLog)->getFillable())->toBe($fillable);
});
