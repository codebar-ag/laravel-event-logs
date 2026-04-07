<?php

namespace CodebarAg\LaravelEventLogs\Tests;

use CodebarAg\LaravelEventLogs\LaravelEventLogsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelEventLogsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('laravel-event-logs', [
            'enabled' => true,
            'connection' => 'testing',
            'exclude_routes' => env('EVENT_LOGS_EXCLUDE_ROUTES', []),
            'sanitize' => [
                'request_headers_exclude' => [
                    'authorization',
                    'cookie',
                    'x-csrf-token',
                ],
                'request_data_exclude' => [
                    'password',
                    'password_confirmation',
                    'token',
                ],
            ],
        ]);

        $app['config']->set('app.env', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');

        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->softDeletes();
            $table->timestamps();
        });

        $migration = include __DIR__.'/../database/migrations/2025_08_09_115521_create_event_logs_table.php';
        $migration->up();

        $responseMetricsMigration = include __DIR__.'/../database/migrations/2026_04_07_120000_add_response_metrics_to_event_logs_table.php';
        $responseMetricsMigration->up();

        $subjectMigration = include __DIR__.'/../database/migrations/2026_04_07_120001_alter_event_logs_subject_id_and_sync_index.php';
        $subjectMigration->up();

        $removeSyncMigration = include __DIR__.'/../database/migrations/2026_04_08_120000_remove_event_logs_outbound_sync_columns.php';
        $removeSyncMigration->up();
    }
}
