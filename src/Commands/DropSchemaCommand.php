<?php

namespace CodebarAg\LaravelEventLogs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class DropSchemaCommand extends Command
{
    protected $signature = 'event-logs:schema:drop {--force : Force the operation without confirmation}';

    protected $description = 'Drop database schema for event logs';

    public function handle(): int
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        $schemaExists = $this->schemaExists($connection, 'event_logs');
        if (! $schemaExists) {
            $this->error('Event logs schema does not exist');

            return self::SUCCESS;
        }

        Schema::connection($connection)->drop('event_logs');

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

    protected function databaseExists($connection, string $database): bool
    {
        return Schema::connection($connection->getName())
            ->getConnection()
            ->table('INFORMATION_SCHEMA.SCHEMATA')
            ->where('SCHEMA_NAME', $database)
            ->exists();
    }
}
