<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            return;
        }

        if (! Schema::connection($connection)->hasTable('event_logs')) {
            return;
        }

        $schema = Schema::connection($connection);

        $schema->table('event_logs', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('event_logs', 'subject_id')) {
                $table->string('subject_id', 36)->nullable()->change();
            }
        });

        $indexName = 'event_logs_sync_pending_index';
        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $indexes = $this->indexNames($connection, 'event_logs', $driver);

        if (! in_array($indexName, $indexes, true)) {
            Schema::connection($connection)->table('event_logs', function (Blueprint $table) use ($indexName) {
                $table->index(['synced_at', 'sync_failed_at'], $indexName);
            });
        }
    }

    public function down(): void
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            return;
        }

        if (! Schema::connection($connection)->hasTable('event_logs')) {
            return;
        }

        $indexName = 'event_logs_sync_pending_index';
        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $indexes = $this->indexNames($connection, 'event_logs', $driver);

        if (in_array($indexName, $indexes, true)) {
            Schema::connection($connection)->table('event_logs', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        Schema::connection($connection)->table('event_logs', function (Blueprint $table) {
            $table->unsignedInteger('subject_id')->nullable()->change();
        });
    }

    /**
     * @return array<int, string>
     */
    private function indexNames(string $connection, string $table, string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = DB::connection($connection)->select("PRAGMA index_list('{$table}')");

            return array_map(static fn ($row) => (string) $row->name, $rows);
        }

        $database = Schema::connection($connection)->getConnection()->getDatabaseName();

        $rows = DB::connection($connection)->select(
            'SELECT DISTINCT INDEX_NAME as name FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, $table]
        );

        return array_map(static fn ($row) => (string) $row->name, $rows);
    }
};
