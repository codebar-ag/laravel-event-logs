<?php

// Altering column types with ->change() requires doctrine/dbal on MySQL, PostgreSQL, and SQL Server.
// See README "Upgrading" and composer.json "suggest".

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
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

        $schema = Schema::connection($connection);

        $schema->table('event_logs', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('event_logs', 'subject_id')) {
                $table->unsignedInteger('subject_id')->nullable()->change();
            }
        });
    }
};
