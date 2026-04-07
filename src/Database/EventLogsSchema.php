<?php

namespace CodebarAg\LaravelEventLogs\Database;

use Illuminate\Database\Schema\Blueprint;

final class EventLogsSchema
{
    public static function define(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('type');

        $table->string('subject_type')->nullable();
        $table->string('subject_id', 36)->nullable();

        $table->string('user_type')->nullable();
        $table->unsignedInteger('user_id')->nullable();

        $table->ipAddress('request_ip')->nullable();
        $table->string('request_method')->nullable();
        $table->text('request_url')->nullable();
        $table->string('request_route')->nullable();

        $table->unsignedSmallInteger('response_status')->nullable();
        $table->unsignedInteger('duration_ms')->nullable();

        $table->json('request_headers')->nullable();
        $table->json('request_data')->nullable();

        $table->string('event')->nullable();
        $table->json('event_data')->nullable();

        $table->json('context')->nullable();

        $table->timestamps();

        $table->index(['uuid']);
        $table->index(['type']);
        $table->index(['subject_type', 'subject_id']);
        $table->index(['user_id', 'user_type']);
        $table->index(['event']);
    }
}
