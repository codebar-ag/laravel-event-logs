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

        $migration = include LaravelEventLogsServiceProvider::createEventLogsMigrationPath();
        $migration->up();
    }
}
