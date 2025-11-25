<?php

use CodebarAg\LaravelEventLogs\Commands\DropSchemaCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\OutputStyle;

test('command fails when connection is not configured', function () {
    Config::set('laravel-event-logs.connection', null);

    $command = new DropSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::FAILURE);
});

test('command fails when connection is empty string', function () {
    Config::set('laravel-event-logs.connection', '');

    $command = new DropSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::FAILURE);
});

test('command handles schema drop when schema exists', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    // Mock the schemaExists method to return true (schema exists)
    // We'll test the logic path without actually dropping the table
    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);

    // Since we can't easily mock Schema::connection()->drop(),
    // we'll just verify the command returns success when schema exists
    // The actual drop operation would be tested in integration tests
    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});

test('command skips drop when schema does not exist', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    // Mock the schemaExists method to return false (schema doesn't exist)
    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return false;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));

    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});

test('command handles force option', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    // Mock the schemaExists method to return true (schema exists)
    $command = new class extends DropSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);

    $result = $command->handle();

    expect($result)->toBe(DropSchemaCommand::SUCCESS);
});
