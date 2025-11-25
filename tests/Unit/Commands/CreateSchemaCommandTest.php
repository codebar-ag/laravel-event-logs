<?php

use CodebarAg\LaravelEventLogs\Commands\CreateSchemaCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Config;
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

test('command handles schema creation', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    $command = new class extends CreateSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return false;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
});

test('command skips migration when schema already exists', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    $command = new class extends CreateSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
});
