# Laravel Event Logs

[![Packagist Version](https://img.shields.io/packagist/v/codebar-ag/laravel-event-logs.svg)](https://packagist.org/packages/codebar-ag/laravel-event-logs)
[![Downloads](https://img.shields.io/packagist/dt/codebar-ag/laravel-event-logs.svg)](https://packagist.org/packages/codebar-ag/laravel-event-logs/stats)
[![License](https://img.shields.io/packagist/l/codebar-ag/laravel-event-logs.svg)](https://github.com/codebar-ag/laravel-event-logs/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/codebar-ag/laravel-event-logs?logo=php&logoColor=white)](https://packagist.org/packages/codebar-ag/laravel-event-logs)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%2B-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PEST](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/pest.yml/badge.svg)](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/pest.yml)
[![PHPStan](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/phpstan.yml/badge.svg)](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/phpstan.yml)

This package provides event logging for HTTP requests and model events. It is provider-agnostic and supports pluggable transports. The initial provider implementation ships an Azure Event Hub sender.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Database Connection](#database-connection)
  - [Schema Management](#schema-management)
- [Usage](#usage)
  - [Middleware Request Logging](#middleware-request-logging)
  - [Model Event Logging](#model-event-logging)
  - [Sending Logs to Azure Event Hub](#sending-logs-to-azure-event-hub)
  - [Adding Context](#adding-context)

## Requirements

- Laravel 12
- PHP 8.4
- Azure Event Hub subscription

## Installation

### Composer Install

```bash
composer require codebar-ag/laravel-event-logs
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="CodebarAg\\LaravelEventLogs\\LaravelEventLogsServiceProvider"
```

This will publish the configuration file to `config/laravel-event-logs.php` where you can customize the package settings.

## Configuration

### Database Connection

By default, the package uses your application's default database connection. You can specify a custom database connection for event logs by setting the `connection` option in your configuration file:

```php
// config/laravel-event-logs.php
return [
    'enabled' => env('EVENT_LOGS_ENABLED', false),
    'connection' => env('EVENT_LOGS_CONNECTION', null), // Set to null to use default connection
    // ... other configuration
];
```

Or via environment variable:

```bash
EVENT_LOGS_CONNECTION=event_logs_db
```

This is useful when you want to store event logs in a separate database for better performance or isolation.

### Schema Management

The package provides Artisan commands to manage the event logs database schema:

#### Create Schema

Create the database schema for event logs:

```bash
php artisan event-logs:schema:create
```

This command will:
- Check if the event logs connection is configured
- Verify if the schema already exists
- Run the migration to create the `event_logs` table if it doesn't exist

**Note**: The command requires the `connection` configuration to be set. If not configured, the command will fail with an error message.

#### Drop Schema

Drop the database schema for event logs:

```bash
php artisan event-logs:schema:drop
```

Or with the force option (no confirmation):

```bash
php artisan event-logs:schema:drop --force
```

This command will:
- Check if the event logs connection is configured
- Verify if the schema exists
- Drop the `event_logs` table if it exists

**Warning**: This will permanently delete all event logs data. Use with caution.

## Usage

### Middleware Request Logging

The `EventLogMiddleware` automatically logs all HTTP requests. Add it to your Laravel 12 application:

#### Configuration

```php
// config/laravel-event-logs.php
return [
    'enabled' => env('EVENT_LOGS_ENABLED', false),
    'connection' => env('EVENT_LOGS_CONNECTION', null),
    'exclude_routes' => [
        'livewire.update',
    ],
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
    'providers' => [
        'azure_event_hub' => [
            'endpoint' => env('AZURE_EVENT_HUB_ENDPOINT'),
            'path' => env('AZURE_EVENT_HUB_PATH'),
            'policy_name' => env('AZURE_EVENT_HUB_POLICY_NAME', 'RootManageSharedAccessKey'),
            'primary_key' => env('AZURE_EVENT_HUB_PRIMARY_KEY'),
        ],
    ],
];
```

#### Implementation

```php
// bootstrap/app.php
use CodebarAg\\LaravelEventLogs\\Middleware\\EventLogMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EventLogMiddleware::class,
        ]);
    })
    ->create();
```

#### Logged Data

The middleware logs these HTTP request details:

- **Request Information**: Method, URL, route name, IP address
- **Request User**: Authenticated user type and ID
- **Request Data**: Sanitized headers and payload
- **Context**: Application context information

#### Example Output

```php
// Using AzureEventHubDTO::toArray()
[
    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    'type' => 'http_request',
    'subject_type' => null,
    'subject_id' => null,
    'user_type' => 'App\Models\User',
    'user_id' => 456,
    'request_route' => 'users.store',
    'request_method' => 'POST',
    'request_url' => 'https://example.com/api/users',
    'request_ip' => '192.168.1.100',
    'request_headers' => ['Content-Type' => 'application/json'],
    'request_data' => ['name' => 'John', 'email' => 'john@example.com'],
    'event' => null,
    'event_data' => null,
    'context' => ['locale' => 'de_CH', 'environment' => 'production'],
    'created_at' => '2024-01-15 10:30:00'
]
```

### Sending Logs to Azure Event Hub

The package provides an action to send event logs to Azure Event Hub. You can process events in background jobs for better performance.

#### Configuration

Add your Azure Event Hub credentials to the published config file under `providers.azure_event_hub`:

```php
// config/laravel-event-logs.php
return [
    'enabled' => env('EVENT_LOGS_ENABLED', false),
    'providers' => [
        'azure_event_hub' => [
            'endpoint' => env('AZURE_EVENT_HUB_ENDPOINT'),
            'path' => env('AZURE_EVENT_HUB_PATH'),
            'policy_name' => env('AZURE_EVENT_HUB_POLICY_NAME', 'RootManageSharedAccessKey'),
            'primary_key' => env('AZURE_EVENT_HUB_PRIMARY_KEY'),
        ],
    ],
];
```

#### Environment Variables

```bash
AZURE_EVENT_HUB_ENDPOINT=https://your-namespace.servicebus.windows.net
AZURE_EVENT_HUB_PATH=your-event-hub-name
AZURE_EVENT_HUB_POLICY_NAME=RootManageSharedAccessKey
AZURE_EVENT_HUB_PRIMARY_KEY=your-primary-key
```

#### Available Methods

- **`(new AzureEventHubAction())->send(EventLog $eventLog): void`**: Sends a single `CodebarAg\LaravelEventLogs\Models\EventLog` to Azure Event Hub using the REST API. The payload is the model's `toArray()` encoded as JSON and sent to `.../messages?api-version=2014-01` with a SAS token in the `Authorization` header`. Guard calls with `config('laravel-event-logs.enabled')` and handle idempotency (e.g., `synced_at`) in your job.

Minimal usage example:

```php
use CodebarAg\LaravelEventLogs\Actions\AzureEventHubAction;
use CodebarAg\LaravelEventLogs\Models\EventLog;

// Send one event (instance API)
(new AzureEventHubAction())->send($eventLog); // $eventLog is an instance of EventLog
```

#### Example Implementation

Create a job to process and send event logs to Azure Event Hub:

```php
<?php

namespace App\Jobs;

use CodebarAg\LaravelEventLogs\Actions\AzureEventHubAction;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAzureEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private EventLog $eventLog
    ) {}

    public function handle(): void
    {
        $enabled = config('laravel-event-logs.enabled');

        if (! $enabled) {
            return;
        }

        if ($this->eventLog->synced_at) {
            return;
        }

        (new AzureEventHubAction())->send($this->eventLog);

        $this->eventLog->update([
            'synced_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->eventLog->update([
            'synced_at' => null,
        ]);
    }
}
```

### Model Event Logging

Use the `HasEventLogTrait` to automatically log model events (created, updated, deleted, restored).

#### Implementation

```php
<?php

namespace App\Models;

use CodebarAg\LaravelEventLogs\Traits\HasEventLogTrait;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasEventLogTrait;
    
    // Your model code...
}
```

#### Logged Data

The trait automatically logs these events:

- **`created`**: When a model is created
- **`updated`**: When a model is updated  
- **`deleted`**: When a model is deleted
- **`restored`**: When a soft-deleted model is restored (if using SoftDeletes)

Each model event logs:
- Event type (created, updated, deleted, restored)
- Model class and ID
- User who triggered the event
- Model attributes (for created events)
- Changes made (for updated events)
- Original values (for updated events)
- Dirty keys (for updated events)
- Context information

#### Example Output

```php
// Using AzureEventHubDTO::toArray() when a User model is created:
[
    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    'type' => 'model',
    'subject_type' => 'App\Models\User',
    'subject_id' => '123',
    'user_type' => 'App\Models\User',
    'user_id' => 456,
    'request_route' => null,
    'request_method' => null,
    'request_url' => null,
    'request_ip' => null,
    'request_headers' => null,
    'request_data' => null,
    'event' => 'created',
    'event_data' => [
        'event' => 'created',
        'model_type' => 'App\Models\User',
        'model_id' => 123,
        'attributes' => ['name' => 'John', 'email' => 'john@example.com'],
        'changes' => [],
        'original' => [],
        'dirty_keys' => [],
    ],
    'context' => ['tenant_id' => 1],
    'created_at' => '2024-01-15 10:30:00'
]
```

### Adding Context

Use Laravel's Context facade to add custom context that will be included in all event logs. For example: create a middleware to set context before the EventLogMiddleware runs:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

class SetRequestContext
{
    public function handle(Request $request, Closure $next)
    {
        // Add request context
        Context::add('locale', app()->getLocale());
        return $next($request);
    }
}
```

Register the context middleware before EventLogMiddleware:

```php
// bootstrap/app.php
use App\Http\Middleware\SetRequestContext;
use CodebarAg\LaravelEventLogs\Middleware\EventLogMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetRequestContext::class,
            EventLogMiddleware::class,
        ]);
    })
    ->create();
```
