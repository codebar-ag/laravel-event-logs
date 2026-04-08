<?php

namespace CodebarAg\LaravelEventLogs\Commands;

use CodebarAg\LaravelEventLogs\Database\EventLogsSchemaUpdater;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

class UpdateSchemaCommand extends Command
{
    protected $signature = 'event-logs:schema:update';

    protected $description = 'Align the event_logs table with the package schema (add missing columns and indexes)';

    public function handle(): int
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (! is_string($connection) || $connection === '') {
            $this->error('Event logs connection is not configured');

            return self::FAILURE;
        }

        try {
            $changes = (new EventLogsSchemaUpdater($connection))->run();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($changes === []) {
            $this->info('Schema is already up to date.');

            return self::SUCCESS;
        }

        foreach ($changes as $line) {
            $this->line($line);
        }

        $this->info('Event logs schema update finished.');

        return self::SUCCESS;
    }
}
