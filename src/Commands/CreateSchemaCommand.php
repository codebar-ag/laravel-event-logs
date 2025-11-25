<?php

namespace CodebarAg\LaravelEventLogs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CreateSchemaCommand extends Command
{
    protected $signature = 'event-logs:schema:create';

    protected $description = 'Create database schema for event logs';

    public function handle(): int
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        $schemaExists = $this->schemaExists($connection, 'event_logs');
        if (! $schemaExists) {
            $this->info('Event logs schema already exists');

            return self::SUCCESS;
        }

        Artisan::call('migrate', [
            '--database' => $connection,
            '--path' => 'database/migrations/2025_08_09_115521_create_event_logs_table.php',
        ]);

        return self::SUCCESS;
    }

    protected function schemaExists($connection, string $schema): bool
    {
        return Schema::connection($connection->getName())
            ->getConnection()
            ->table('information_schema.schemata')
            ->where('schema_name', $schema)
            ->exists();
    }
}
