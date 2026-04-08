# Changelog

All notable changes to this project are documented in this file.

## Unreleased

### Added

- `php artisan event-logs:schema:update` and [`EventLogsSchemaUpdater`](src/Database/EventLogsSchemaUpdater.php) to align an existing `event_logs` table (add missing columns/indexes, optional `subject_id` string conversion).
- [`EventLogsSchema`](src/Database/EventLogsSchema.php) as the single blueprint definition shared by the migration and the schema updater.
- Composer `suggest` for `doctrine/dbal` when `event-logs:schema:update` runs `subject_id` `->change()` on MySQL/PostgreSQL/SQL Server.
- Single migration [`2026_04_10_000000_create_event_logs_table.php`](database/migrations/2026_04_10_000000_create_event_logs_table.php) creates the full `event_logs` schema in one step (idempotent when the table already exists); `up()` delegates to `EventLogsSchema::define()`.
- `config/laravel-event-logs-exclude-routes-defaults.php` for default Nova/Livewire-related `exclude_routes` prefix patterns; published alongside `laravel-event-logs.php` under tag `laravel-event-logs-config`.
- [`RequestUserResolver`](src/Support/RequestUserResolver.php) for HTTP user resolution (used by `EventLogMiddleware`).
- `LaravelEventLogsServiceProvider::CREATE_EVENT_LOGS_MIGRATION` and `createEventLogsMigrationPath()` for a stable path to the package migration.

### Removed

- Azure Event Hubs integration: `EventLogTransport`, `AzureEventHubTransport`, `AzureEventHubAction`, `AzureEventHubDTO`, and all related config (`providers`, `default_transport`, `transports`, `legacy_to_array_provider_payload`, SAS/env settings).
- Outbound sync tracking: `synced_at`, `sync_failed_at`, `EventLog::scopeUnsynced()`, composite index `event_logs_sync_pending_index`, `EventLog::toProviderPayload()`, and legacy Azure-shaped `EventLog::toArray()` behavior.
- Previous multi-step migrations (`2025_08_09_*`, `2026_04_07_*`, `2026_04_08_*`) replaced by the single migration above.

### Changed

- Default `exclude_routes_match` is `auto` so prefix/wildcard patterns work; set `EVENT_LOGS_EXCLUDE_ROUTES_MATCH=exact` if you only list full route names.
- Shipped `exclude_routes` defaults are shortened to prefix entries (`livewire-filepond.`, `livewire.`, `nova.`) instead of enumerating every Nova/Livewire route name.
- `event_logs.uuid`: drop redundant non-unique index where `->unique()` already applies; `event-logs:schema:update` adds a unique index on `uuid` for legacy tables instead of a plain index.
- `event-logs:schema:create` publishes migrations if needed, runs `migrate` with path `database/migrations/2026_04_10_000000_create_event_logs_table.php`, checks exit code, and verifies `event_logs` exists.
- `EventLogMiddleware` uses `normalizeStringList()` for config arrays and delegates user resolution to `RequestUserResolver`.
- Package focus is **database-only** logging on the configured `connection` (HTTP middleware, model trait, optional queued persist via `RecordEventLogJob`).
- `EventLog::toArray()` uses the default Eloquent serialization.

### Fixed

- `RecordEventLogJob` returns early when `EventLog::isEnabled()` is false so queued work respects runtime config.
- Queued persistence no longer inserts rows after logging is disabled or the connection is cleared between dispatch and handle.

### Notes for upgrades

- Delete old **published** migration copies from your app if you had `2025_08_09_*`, `2026_04_07_*`, or `2026_04_08_*`, then republish or add only `2026_04_10_000000_create_event_logs_table.php`.
- Republish config to receive `laravel-event-logs-exclude-routes-defaults.php` if you rely on published files. If you already published it, compare with the package version: defaults are now three prefix patterns (`livewire-filepond.`, `livewire.`, `nova.`) and `exclude_routes_match` defaults to `auto`.
- Remove any app code that called `toProviderPayload()`, `EventLogTransport`, or Azure env variables.
- Register `EventLogMiddleware` as terminable (normal Laravel `append` usage is sufficient).
