<?php

use CodebarAg\LaravelEventLogs\Database\EventLogsSchema;
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

        if (Schema::connection($connection)->hasTable('event_logs')) {
            return;
        }

        Schema::connection($connection)->create('event_logs', function (Blueprint $table) {
            EventLogsSchema::define($table);
        });
    }

    public function down(): void
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            return;
        }

        Schema::connection($connection)->dropIfExists('event_logs');
    }
};
