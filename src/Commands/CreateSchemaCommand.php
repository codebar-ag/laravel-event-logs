<?php

namespace CodebarAg\LaravelEventLogs\Commands;

use CodebarAg\LaravelEventLogs\LaravelEventLogsServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CreateSchemaCommand extends Command
{
    protected $signature = 'event-logs:schema:create';

    protected $description = 'Create database schema for event logs';

    public function handle(): int
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (! is_string($connection) || $connection === '') {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        if (Schema::connection($connection)->hasTable('event_logs')) {
            $this->info('Event logs table already exists');

            return self::SUCCESS;
        }

        $migrationFile = LaravelEventLogsServiceProvider::createEventLogsMigrationPath();
        $migrationBasename = LaravelEventLogsServiceProvider::CREATE_EVENT_LOGS_MIGRATION;
        $publishedMigrationPath = database_path('migrations/'.$migrationBasename);

        if (! File::exists($migrationFile)) {
            $this->error('Migration file not found. Please publish migrations first:');
            $this->line('php artisan vendor:publish --tag=laravel-event-logs-migrations');

            return self::FAILURE;
        }

        if (! File::exists($publishedMigrationPath)) {
            $this->warn('Migrations are not published. Publishing them now...');

            Artisan::call('vendor:publish', [
                '--tag' => 'laravel-event-logs-migrations',
                '--force' => true,
            ]);

            $this->info('Migrations published.');
        }

        if (! File::exists($publishedMigrationPath)) {
            $this->error('Could not publish migrations to database/migrations.');

            return self::FAILURE;
        }

        $this->info('Running migration...');

        $relativePath = 'database/migrations/'.$migrationBasename;

        $exitCode = Artisan::call('migrate', [
            '--database' => $connection,
            '--path' => $relativePath,
        ]);

        if ($exitCode !== 0) {
            $this->error(trim(Artisan::output()));

            return self::FAILURE;
        }

        $this->info('Event logs table created successfully');

        return self::SUCCESS;
    }
}
