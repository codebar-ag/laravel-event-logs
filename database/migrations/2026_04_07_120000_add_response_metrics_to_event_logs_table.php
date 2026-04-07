<?php

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

        Schema::connection($connection)->table('event_logs', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('event_logs', 'response_status')) {
                $table->unsignedSmallInteger('response_status')->nullable();
            }
            if (! Schema::connection($connection)->hasColumn('event_logs', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable();
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

        Schema::connection($connection)->table('event_logs', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('event_logs', 'duration_ms')) {
                $table->dropColumn('duration_ms');
            }
            if (Schema::connection($connection)->hasColumn('event_logs', 'response_status')) {
                $table->dropColumn('response_status');
            }
        });
    }
};
