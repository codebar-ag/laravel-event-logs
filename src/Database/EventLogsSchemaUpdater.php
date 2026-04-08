<?php

namespace CodebarAg\LaravelEventLogs\Database;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EventLogsSchemaUpdater
{
    private const TABLE = 'event_logs';

    public function __construct(
        private readonly string $connection,
    ) {}

    /**
     * @return list<string>
     */
    public function run(): array
    {
        $changes = [];
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable(self::TABLE)) {
            $schema->create(self::TABLE, function (Blueprint $table) {
                EventLogsSchema::define($table);
            });
            $changes[] = 'Created table '.self::TABLE;

            return $changes;
        }

        $this->addMissingColumns($changes);
        $this->upgradeSubjectIdIfNeeded($changes);
        $this->ensureIndexes($changes);

        return $changes;
    }

    /**
     * @param  list<string>  $changes
     */
    private function addMissingColumns(array &$changes): void
    {
        $schema = Schema::connection($this->connection);

        $add = function (string $column, Closure $callback) use (&$changes, $schema): void {
            if ($schema->hasColumn(self::TABLE, $column)) {
                return;
            }
            $schema->table(self::TABLE, $callback);
            $changes[] = 'Added column: '.$column;
        };

        $add('id', fn (Blueprint $table) => $table->id());
        $add('uuid', fn (Blueprint $table) => $table->uuid('uuid')->unique());
        $add('type', fn (Blueprint $table) => $table->string('type'));
        $add('subject_type', fn (Blueprint $table) => $table->string('subject_type')->nullable());
        $add('subject_id', fn (Blueprint $table) => $table->string('subject_id', 36)->nullable());
        $add('user_type', fn (Blueprint $table) => $table->string('user_type')->nullable());
        $add('user_id', fn (Blueprint $table) => $table->unsignedInteger('user_id')->nullable());
        $add('request_ip', fn (Blueprint $table) => $table->ipAddress('request_ip')->nullable());
        $add('request_method', fn (Blueprint $table) => $table->string('request_method')->nullable());
        $add('request_url', fn (Blueprint $table) => $table->text('request_url')->nullable());
        $add('request_route', fn (Blueprint $table) => $table->string('request_route')->nullable());
        $add('response_status', fn (Blueprint $table) => $table->unsignedSmallInteger('response_status')->nullable());
        $add('duration_ms', fn (Blueprint $table) => $table->unsignedInteger('duration_ms')->nullable());
        $add('request_headers', fn (Blueprint $table) => $table->json('request_headers')->nullable());
        $add('request_data', fn (Blueprint $table) => $table->json('request_data')->nullable());
        $add('event', fn (Blueprint $table) => $table->string('event')->nullable());
        $add('event_data', fn (Blueprint $table) => $table->json('event_data')->nullable());
        $add('context', fn (Blueprint $table) => $table->json('context')->nullable());

        if (! $schema->hasColumn(self::TABLE, 'created_at')) {
            $schema->table(self::TABLE, fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
            $changes[] = 'Added column: created_at';
        }
        if (! $schema->hasColumn(self::TABLE, 'updated_at')) {
            $schema->table(self::TABLE, fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
            $changes[] = 'Added column: updated_at';
        }
    }

    /**
     * @param  list<string>  $changes
     */
    private function upgradeSubjectIdIfNeeded(array &$changes): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasColumn(self::TABLE, 'subject_id')) {
            return;
        }

        try {
            $type = $schema->getColumnType(self::TABLE, 'subject_id');
        } catch (Throwable) {
            return;
        }

        if (! in_array($type, ['integer', 'bigint', 'int'], true)) {
            return;
        }

        try {
            $schema->table(self::TABLE, function (Blueprint $table) {
                $table->string('subject_id', 36)->nullable()->change();
            });
            $changes[] = 'Altered column subject_id to string(36) for UUID compatibility';
        } catch (Throwable $e) {
            $changes[] = 'Skipped subject_id conversion (install doctrine/dbal on MySQL/PostgreSQL/SQL Server if needed): '.$e->getMessage();
        }
    }

    /**
     * @param  list<string>  $changes
     */
    private function ensureIndexes(array &$changes): void
    {
        $this->tryAddUnique(fn (Blueprint $table) => $table->unique(['uuid']), $changes, 'uuid');
        $this->tryAddIndex(fn (Blueprint $table) => $table->index(['type']), $changes, 'type');
        $this->tryAddIndex(fn (Blueprint $table) => $table->index(['subject_type', 'subject_id']), $changes, 'subject_type, subject_id');
        $this->tryAddIndex(fn (Blueprint $table) => $table->index(['user_id', 'user_type']), $changes, 'user_id, user_type');
        $this->tryAddIndex(fn (Blueprint $table) => $table->index(['event']), $changes, 'event');
    }

    /**
     * @param  list<string>  $changes
     */
    private function tryAddIndex(Closure $callback, array &$changes, string $label): void
    {
        try {
            Schema::connection($this->connection)->table(self::TABLE, $callback);
            $changes[] = 'Added index: '.$label;
        } catch (QueryException $e) {
            if ($this->isDuplicateIndexException($e)) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param  list<string>  $changes
     */
    private function tryAddUnique(Closure $callback, array &$changes, string $label): void
    {
        try {
            Schema::connection($this->connection)->table(self::TABLE, $callback);
            $changes[] = 'Added unique index: '.$label;
        } catch (QueryException $e) {
            if ($this->isDuplicateIndexException($e)) {
                return;
            }
            throw $e;
        }
    }

    private function isDuplicateIndexException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'already exists')) {
            return true;
        }

        if (str_contains($message, 'duplicate key name')) {
            return true;
        }

        return str_contains($message, 'duplicate') && str_contains($message, 'index');
    }
}
