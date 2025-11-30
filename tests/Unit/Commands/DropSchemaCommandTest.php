<?php

use CodebarAg\LaravelEventLogs\Commands\DropSchemaCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

test('command fails when connection is not configured', function () {
    Config::set('laravel-event-logs.connection', null);

    $command = new DropSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::FAILURE);
});

test('command fails when connection is empty string', function () {
    Config::set('laravel-event-logs.connection', '');

    $command = new DropSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::FAILURE);
});

test('command handles schema drop when schema exists', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});

test('command skips drop when schema does not exist', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return false;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});

test('command handles force option', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});
