<?php

use CodebarAg\LaravelEventLogs\Contracts\EventLogTransport;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\LaravelEventLogsServiceProvider;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Transports\AzureEventHubTransport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

test('service provider registers config', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->register();

    expect(Config::get('laravel-event-logs'))->toBeArray();
    expect(Config::get('laravel-event-logs.enabled'))->not->toBeNull();
});

test('service provider binds event log transport', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->register();

    expect($this->app->make(EventLogTransport::class))->toBeInstanceOf(AzureEventHubTransport::class);
});

test('service provider publishes config file', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    expect(config('laravel-event-logs'))->toBeArray();
    expect(config('laravel-event-logs.enabled'))->not->toBeNull();
});

test('service provider registers commands when running in console', function () {
    $this->app->detectEnvironment(fn () => 'testing');

    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    expect(Artisan::all())->toHaveKey('event-logs:schema:create');
    expect(Artisan::all())->toHaveKey('event-logs:schema:drop');
});

test('service provider registers observer for EventLog model', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    $eventLog = new EventLog;
    $eventLog->type = EventLogTypeEnum::HTTP;

    $eventLog->save();

    expect($eventLog->uuid)->not->toBeNull();
});

test('service provider loads migrations when running in console', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    expect(Schema::hasTable('event_logs'))->toBeTrue();
});
