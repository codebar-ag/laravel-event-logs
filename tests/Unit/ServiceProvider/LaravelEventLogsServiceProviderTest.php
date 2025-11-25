<?php

use CodebarAg\LaravelEventLogs\LaravelEventLogsServiceProvider;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

test('service provider registers config', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->register();

    expect(Config::get('laravel-event-logs'))->toBeArray();
    expect(Config::get('laravel-event-logs.enabled'))->not->toBeNull();
});

test('service provider publishes config file', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    // Verify that the config is published by checking if it's available
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

    // Create an event log and verify observer is working
    $eventLog = new EventLog;
    $eventLog->type = \CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum::HTTP;

    // The observer should set uuid automatically
    $eventLog->save();

    expect($eventLog->uuid)->not->toBeNull();
});

test('service provider loads migrations when running in console', function () {
    $provider = new LaravelEventLogsServiceProvider($this->app);
    $provider->boot();

    // Verify migrations are loaded by checking if table exists
    expect(Schema::hasTable('event_logs'))->toBeTrue();
});
