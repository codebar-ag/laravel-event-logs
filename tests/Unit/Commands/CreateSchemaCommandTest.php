<?php

use CodebarAg\LaravelEventLogs\Commands\CreateSchemaCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\OutputStyle;

test('command fails when connection is not configured', function () {
    Config::set('laravel-event-logs.connection', null);

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::FAILURE);
});

test('command fails when connection is empty string', function () {
    Config::set('laravel-event-logs.connection', '');

    $command = new CreateSchemaCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));
    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::FAILURE);
});

test('command handles schema creation', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    // Mock the schemaExists method to return false (schema doesn't exist)
    $command = new class extends CreateSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return false;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', [
            '--database' => 'testing',
            '--path' => 'database/migrations/2025_08_09_115521_create_event_logs_table.php',
        ]);

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
});

test('command skips migration when schema already exists', function () {
    Config::set('laravel-event-logs.connection', 'testing');

    // Mock the schemaExists method to return true (schema exists)
    $command = new class extends CreateSchemaCommand
    {
        protected function schemaExists(string $connection, string $schema): bool
        {
            return true;
        }
    };
    $command->setLaravel($this->app);
    $command->setOutput(new OutputStyle(new BufferedOutput, new BufferedOutput));

    Artisan::shouldReceive('call')->never();

    $result = $command->handle();

    expect($result)->toBe(CreateSchemaCommand::SUCCESS);
});
