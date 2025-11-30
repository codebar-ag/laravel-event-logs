<?php

namespace CodebarAg\LaravelEventLogs;

use CodebarAg\LaravelEventLogs\Commands\CreateSchemaCommand;
use CodebarAg\LaravelEventLogs\Commands\DropSchemaCommand;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Observers\EventLogObserver;
use Illuminate\Support\ServiceProvider;

class LaravelEventLogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-event-logs.php', 'laravel-event-logs');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-event-logs.php' => config_path('laravel-event-logs.php'),
        ], 'laravel-event-logs-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'laravel-event-logs-migrations');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            $this->commands([
                CreateSchemaCommand::class,
                DropSchemaCommand::class,
            ]);
        }

        EventLog::observe(EventLogObserver::class);
    }
}
