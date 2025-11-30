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

        if (empty($connection) || ! is_string($connection)) {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        $tableExists = Schema::connection($connection)->hasTable('event_logs');
        if (! $tableExists) {
            $this->error('Event logs table does not exist');

            return self::SUCCESS;
        }

        Schema::connection($connection)->drop('event_logs');

        $this->info('Event logs table dropped successfully');

        return self::SUCCESS;
    }
}
