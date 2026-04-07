# Changelog

All notable changes to this project are documented in this file.

## Unreleased

### Removed

- Azure Event Hubs integration: `EventLogTransport`, `AzureEventHubTransport`, `AzureEventHubAction`, `AzureEventHubDTO`, and all related config (`providers`, `default_transport`, `transports`, `legacy_to_array_provider_payload`, SAS/env settings).
- Outbound sync tracking: `synced_at`, `sync_failed_at`, `EventLog::scopeUnsynced()`, composite index `event_logs_sync_pending_index`, `EventLog::toProviderPayload()`, and legacy Azure-shaped `EventLog::toArray()` behavior.

### Added

- Migration `2026_04_08_120000_remove_event_logs_outbound_sync_columns.php` to drop sync columns and the sync index on existing databases.
- `RecordEventLogJob` returns early when `EventLog::isEnabled()` is false so queued work respects runtime config.
- Composer `suggest` for `doctrine/dbal` when running `subject_id` `->change()` migrations on MySQL/PostgreSQL/SQL Server; README upgrading note.

### Fixed

- Queued persistence no longer inserts rows after logging is disabled or the connection is cleared between dispatch and handle.

### Changed

- Package focus is **database-only** logging on the configured `connection` (HTTP middleware, model trait, optional queued persist via `RecordEventLogJob`).
- `EventLog::toArray()` uses the default Eloquent serialization.

### Notes for upgrades

- Run `php artisan migrate` (or republish migrations then migrate) so `2026_04_08_120000_remove_event_logs_outbound_sync_columns` runs on apps that still have `synced_at` / `sync_failed_at` / the sync index.
- Remove any app code that called `toProviderPayload()`, `EventLogTransport`, or Azure env variables.
- Register `EventLogMiddleware` as terminable (normal Laravel `append` usage is sufficient).
