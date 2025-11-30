<?php

namespace CodebarAg\LaravelEventLogs\Commands;

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

        if (empty($connection) || ! is_string($connection)) {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        $tableExists = Schema::connection($connection)->hasTable('event_logs');
        if ($tableExists) {
            $this->info('Event logs table already exists');

            return self::SUCCESS;
        }

        $publishedMigrationPath = database_path('migrations/2025_08_09_115521_create_event_logs_table.php');
        $migrationFile = __DIR__.'/../../database/migrations/2025_08_09_115521_create_event_logs_table.php';

        $migrationExists = File::exists($migrationFile);
        if (! $migrationExists) {
            $this->error('Migration file not found. Please publish migrations first:');
            $this->line('php artisan vendor:publish --tag=laravel-event-logs-migrations');

            return self::FAILURE;
        }

        $migrationsPublished = File::exists($publishedMigrationPath);
        if (! $migrationsPublished) {
            $this->warn('Migrations are not published. Publishing them now...');

            Artisan::call('vendor:publish', [
                '--tag' => 'laravel-event-logs-migrations',
                '--force' => true,
            ]);

            $this->info('Migrations published.');
        }

        $this->info('Running migration...');

        Artisan::call('migrate', [
            '--database' => $connection,
            '--path' => 'database/migrations/2025_08_09_115521_create_event_logs_table.php',
        ]);

        $this->info('Event logs table created successfully');

        return self::SUCCESS;
    }
}
