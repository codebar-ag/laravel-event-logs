# Laravel Event Logs

[![Packagist Version](https://img.shields.io/packagist/v/codebar-ag/laravel-event-logs.svg)](https://packagist.org/packages/codebar-ag/laravel-event-logs)
[![Downloads](https://img.shields.io/packagist/dt/codebar-ag/laravel-event-logs.svg)](https://packagist.org/packages/codebar-ag/laravel-event-logs/stats)
[![License](https://img.shields.io/packagist/l/codebar-ag/laravel-event-logs.svg)](https://github.com/codebar-ag/laravel-event-logs/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/codebar-ag/laravel-event-logs?logo=php&logoColor=white)](https://packagist.org/packages/codebar-ag/laravel-event-logs)
[![Laravel Version](https://img.shields.io/badge/Laravel-13.x%2B-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Tests](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/run-tests.yml/badge.svg)](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/phpstan.yml/badge.svg)](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/phpstan.yml)
[![Coverage](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/pest-coverage.yml/badge.svg)](https://github.com/codebar-ag/laravel-event-logs/actions/workflows/pest-coverage.yml)

This package records HTTP requests and model lifecycle events as rows in your database. Configure a **dedicated database connection** for the `event_logs` table so logging stays isolated from your primary application data.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Database Connection](#database-connection)
  - [Schema Management](#schema-management)
  - [Configuration reference](#configuration-reference)
- [Upgrading](#upgrading)
- [Usage](#usage)
  - [Middleware Request Logging](#middleware-request-logging)
  - [Model Event Logging](#model-event-logging)
  - [Adding Context](#adding-context)

## Requirements

- Laravel 13+
- PHP 8.3+
- A database connection for event logs (can be your default connection, but a separate connection is recommended)

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

### Publish Migrations (Optional)

If you want to customize the migrations or need them in your application's `database/migrations` directory, you can publish them:

```bash
php artisan vendor:publish --tag=laravel-event-logs-migrations
```

**Note**: Migrations are automatically loaded from the package, so publishing is optional. However, if you plan to use the `event-logs:schema:create` command, it's recommended to publish the migrations first, or the command will automatically publish them for you.

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

When `enabled` is `true`, `EventLog::isEnabled()` also requires `connection` to be a **non-empty** string. Set `EVENT_LOGS_CONNECTION` (or the config value) to your connection name; use your app’s default connection name explicitly if you do not use a dedicated logs connection.

### Schema Management

The package provides Artisan commands to manage the event logs database schema:

#### Create Schema

Create the database schema for event logs:

```bash
php artisan event-logs:schema:create
```

This command will:
- Check if the event logs connection is configured
- Verify if the `event_logs` table already exists
- Run the migration to create the `event_logs` table if it doesn't exist
- Automatically publish migrations if they haven't been published yet

**Note**: The command requires the `connection` configuration to be set. If not configured, the command will fail with an error message. If migrations are not published, the command will automatically publish them before running the migration.

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

### Configuration reference

Published defaults live in `config/laravel-event-logs.php`. Common keys (see the file for the full list and inline comments):

| Area | Keys / env |
|------|------------|
| Feature toggle | `enabled` (`EVENT_LOGS_ENABLED`) |
| DB connection | `connection` (`EVENT_LOGS_CONNECTION`) — required when enabled |
| Writes | `persist_mode` (`EVENT_LOGS_PERSIST_MODE`: `sync` or `queued`), `queue.connection`, `queue.queue` |
| Sanitization | `sanitize.request_headers_exclude`, `sanitize.request_data_exclude` |
| Context stored on rows | `context.enabled`, `context.allow_keys` (comma-separated env `EVENT_LOGS_CONTEXT_ALLOW_KEYS`), `context.max_keys`, `context.max_json_bytes` |
| HTTP user lookup | `user_resolution.guards`, `user_resolution.scan_all_guards` |
| Route skipping | `exclude_routes`, `exclude_routes_match` (`EVENT_LOGS_EXCLUDE_ROUTES_MATCH`: `exact`, `wildcard`, `auto`) |

## Upgrading

The migration that changes `subject_id` from integer to string ([`2026_04_07_120001_alter_event_logs_subject_id_and_sync_index.php`](database/migrations/2026_04_07_120001_alter_event_logs_subject_id_and_sync_index.php)) uses `->change()`. On **MySQL, PostgreSQL, and SQL Server**, Laravel needs the Doctrine DBAL package for that operation. Install it in your application before migrating:

```bash
composer require doctrine/dbal
```

SQLite (including typical local tests) usually does not require DBAL for this change.

Run new package migrations (or republish and migrate) so your `event_logs` table gains:

- `response_status` and `duration_ms` (HTTP rows are written in `terminate()` so status and timing are available)
- `subject_id` as a string (UUID-friendly)
- Removal of `synced_at`, `sync_failed_at`, and the `event_logs_sync_pending_index` index (database-only package; migration `2026_04_08_120000_remove_event_logs_outbound_sync_columns.php`)

**HTTP middleware** must remain a **terminable** middleware in the stack (Laravel invokes `terminate()` automatically when registered via `append` / the HTTP kernel). If you only call `handle()` in custom tests, call `terminate($request, $response)` yourself.

**Breaking changes (recent versions)**

- Azure Event Hubs and `EventLogTransport` were removed; logs are stored only in the database.
- `EventLog::toProviderPayload()`, `legacy_to_array_provider_payload`, and Azure-shaped `toArray()` are removed; use normal Eloquent `toArray()` / API resources as needed.
- `exclude_routes_match` defaults to `exact`. Use `auto` or `wildcard` so patterns like `nova.api.` or `livewire.*` work as intended.

## Usage

### Middleware Request Logging

The `EventLogMiddleware` automatically logs all HTTP requests after the response is available. Add it to your application:

#### Configuration

```php
// config/laravel-event-logs.php (illustrative — publish for full defaults)
return [
    'enabled' => env('EVENT_LOGS_ENABLED', false),
    'connection' => env('EVENT_LOGS_CONNECTION', null), // non-empty when enabled
    'persist_mode' => env('EVENT_LOGS_PERSIST_MODE', 'sync'),
    'exclude_routes_match' => env('EVENT_LOGS_EXCLUDE_ROUTES_MATCH', 'exact'),
    'exclude_routes' => [
        'livewire.update',
    ],
    'user_resolution' => [
        'guards' => null, // or e.g. ['web', 'sanctum']
        'scan_all_guards' => false,
    ],
    'context' => [
        'enabled' => true,
        'allow_keys' => [], // empty = all keys; or restrict via EVENT_LOGS_CONTEXT_ALLOW_KEYS
        'max_keys' => null,
        'max_json_bytes' => null,
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
            '_token',
            'token',
        ],
    ],
];
```

#### Implementation

```php
// bootstrap/app.php
use CodebarAg\LaravelEventLogs\Middleware\EventLogMiddleware;
use Illuminate\Foundation\Configuration\Middleware;

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
- **Response**: HTTP status code and duration in milliseconds
- **Request User**: Authenticated user type and ID (see `user_resolution` in config)
- **Request Data**: Sanitized headers and payload
- **Context**: Filtered application context (`context.*` config: allowlist, max keys, max JSON bytes)

Optional: set `persist_mode` to `queued` and configure `queue.connection` / `queue.queue` to write rows via `RecordEventLogJob`.

#### Example Output

```php
// Illustrative attributes persisted on the event_logs row (see EventLog model / toArray())
[
    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    'type' => 'http',
    'subject_type' => null,
    'subject_id' => null,
    'user_type' => 'App\Models\User',
    'user_id' => 456,
    'request_route' => 'users.store',
    'response_status' => 201,
    'duration_ms' => 42,
    'request_method' => 'POST',
    'request_url' => 'https://example.com/api/users',
    'request_ip' => '192.168.1.100',
    'request_headers' => ['Content-Type' => 'application/json'],
    'request_data' => ['name' => 'John', 'email' => 'john@example.com'],
    'event' => null,
    'event_data' => null,
    'context' => ['locale' => 'de_CH', 'environment' => 'production'],
    'created_at' => '2024-01-15T10:30:00+00:00',
]
```

### Model Event Logging

Use the `HasEventLogTrait` to automatically log model events (created, updated, deleted, restored). Rows are written through the same `EventLogRecorder` as HTTP logs, so `persist_mode` (`sync` or `queued`) applies here too.

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
// Illustrative attributes when a User model is created:
[
    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    'type' => 'model',
    'subject_type' => 'App\Models\User',
    'subject_id' => '123',
    'user_type' => 'App\Models\User',
    'user_id' => 456,
    'request_route' => null,
    'response_status' => null,
    'duration_ms' => null,
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
    'created_at' => '2024-01-15T10:30:00+00:00',
]
```

### Adding Context

Use Laravel’s `Context` facade to add data that can be persisted on `event_logs` rows. What actually gets stored is filtered by the `context` config (`enabled`, optional `allow_keys`, `max_keys`, `max_json_bytes`) — see [Configuration reference](#configuration-reference).

For example, set context in middleware that runs **before** `EventLogMiddleware`:

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
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetRequestContext::class,
            EventLogMiddleware::class,
        ]);
    })
    ->create();
```
