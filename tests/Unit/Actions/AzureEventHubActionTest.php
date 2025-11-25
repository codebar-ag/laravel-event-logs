<?php

use CodebarAg\LaravelEventLogs\Actions\AzureEventHubAction;
use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Support\Facades\Http;

test('azure event hub action can generate token', function () {
    $token = (new AzureEventHubAction)->buildToken();

    expect($token)->toContain('SharedAccessSignature');
    expect($token)->toContain('sr=');
    expect($token)->toContain('sig=');
    expect($token)->toContain('se=');
    expect($token)->toContain('skn=RootManageSharedAccessKey');
});

test('azure event hub action can send event log model', function () {
    Http::fake([
        'https://test-namespace.servicebus.windows.net/test-event-hub/messages*' => Http::response('OK', 200),
    ]);

    $eventLog = EventLog::create([
        'uuid' => 'test-uuid',
        'type' => EventLogTypeEnum::HTTP,
        'event' => EventLogEventEnum::CREATED,
        'request_method' => 'POST',
        'request_url' => 'https://example.com/api/test',
        'request_ip' => '127.0.0.1',
    ]);

    expect(fn () => (new AzureEventHubAction)->send($eventLog))->not->toThrow(\Throwable::class);
});

test('azure event hub action handles malformed data gracefully', function () {
    Http::fake([
        'https://test-namespace.servicebus.windows.net/test-event-hub/messages*' => Http::response('OK', 200),
    ]);

    /** @var mixed $malformedData */
    $malformedData = null;

    expect(fn () => (new AzureEventHubAction)->send($malformedData)) // @phpstan-ignore-line
        ->toThrow(\TypeError::class);
});

test('azure event hub configuration is properly loaded', function () {
    $config = config('laravel-event-logs');

    expect($config)->toHaveKey('enabled');
    expect($config)->toHaveKey('providers');
    expect($config['providers'])->toHaveKey('azure_event_hub');

    $azure = $config['providers']['azure_event_hub'];
    expect($azure)->toHaveKey('endpoint');
    expect($azure)->toHaveKey('path');
    expect($azure)->toHaveKey('policy_name');
    expect($azure)->toHaveKey('primary_key');

    expect($config['enabled'])->toBeTrue();
    expect($azure['endpoint'])->toBe('https://test-namespace.servicebus.windows.net');
    expect($azure['path'])->toBe('test-event-hub');
    expect($azure['policy_name'])->toBe('RootManageSharedAccessKey');
    expect($azure['primary_key'])->toBe('test-primary-key-for-testing-only');
});
