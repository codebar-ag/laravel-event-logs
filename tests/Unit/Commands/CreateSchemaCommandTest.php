<?php

use CodebarAg\LaravelEventLogs\Commands\CreateSchemaCommand;
use CodebarAg\LaravelEventLogs\LaravelEventLogsServiceProvider;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

test('command fails when connection is not configured', function () {
    Config::set('laravel-event-logs.connection', null);

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::FAILURE);
});

test('command fails when connection is empty string', function () {
    Config::set('laravel-event-logs.connection', '');

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::FAILURE);
});

test('command succeeds when event logs table already exists', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeTrue();

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
});

test('command runs migration when event logs table is missing', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    Schema::connection('testing')->dropIfExists('event_logs');
    if (Schema::connection('testing')->hasTable('migrations')) {
        DB::connection('testing')->table('migrations')
            ->where('migration', '2026_04_10_000000_create_event_logs_table')
            ->delete();
    }
    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeFalse();

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'response_status'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'duration_ms'))->toBeTrue();
});

test('migration file is registered on service provider', function () {
    expect(file_exists(LaravelEventLogsServiceProvider::createEventLogsMigrationPath()))->toBeTrue();
});
