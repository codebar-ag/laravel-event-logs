<?php

use CodebarAg\LaravelEventLogs\Commands\UpdateSchemaCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

test('update schema command fails when connection is not configured', function () {
    Config::set('laravel-event-logs.connection', null);

    $command = new UpdateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(UpdateSchemaCommand::FAILURE);
});

test('update schema command creates event_logs when table is missing', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    Schema::connection('testing')->dropIfExists('event_logs');
    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeFalse();

    $command = new UpdateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(UpdateSchemaCommand::SUCCESS);
    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'uuid'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'response_status'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'duration_ms'))->toBeTrue();
});

test('update schema command adds missing columns to partial table', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    Schema::connection('testing')->dropIfExists('event_logs');
    Schema::connection('testing')->create('event_logs', function (Blueprint $table) {
        $table->id();
    });
    expect(Schema::connection('testing')->hasColumn('event_logs', 'uuid'))->toBeFalse();

    $command = new UpdateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(UpdateSchemaCommand::SUCCESS);
    expect(Schema::connection('testing')->hasColumn('event_logs', 'uuid'))->toBeTrue();
    expect(Schema::connection('testing')->hasColumn('event_logs', 'type'))->toBeTrue();
});

test('update schema command reports up to date when schema is complete', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    expect(Schema::connection('testing')->hasTable('event_logs'))->toBeTrue();

    $command = new UpdateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(UpdateSchemaCommand::SUCCESS);
});
