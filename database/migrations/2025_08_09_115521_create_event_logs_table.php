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

        $schemaExists = Schema::connection($connection)->hasTable('event_logs');

        if ($schemaExists) {
            return;
        }

        Schema::connection($connection)->create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');

            $table->string('subject_type')->nullable();
            $table->unsignedInteger('subject_id')->nullable();

            $table->string('user_type')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->ipAddress('request_ip')->nullable();
            $table->string('request_method')->nullable();
            $table->text('request_url')->nullable();
            $table->string('request_route')->nullable();

            $table->json('request_headers')->nullable();
            $table->json('request_data')->nullable();

            $table->string('event')->nullable();
            $table->json('event_data')->nullable();

            $table->json('context')->nullable();

            $table->dateTime('synced_at')->nullable();
            $table->dateTime('sync_failed_at')->nullable();

            $table->timestamps();

            $table->index(['uuid']);
            $table->index(['type']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'user_type']);
            $table->index(['event']);
        });
    }

    public function down(): void
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (empty($connection)) {
            return;
        }

        Schema::connection($connection)->drop('event_logs');
    }
};
