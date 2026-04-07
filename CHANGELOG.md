# Changelog

All notable changes to this project are documented in this file.

## Unreleased

### Added

- `ContextExporter` with `context.*` config (allowlist, `max_keys`, `max_json_bytes`, enable/disable).
- `exclude_routes_match`: `exact` (default), `wildcard`, or `auto` (prefix / glob) via `RouteExclusion`.
- `user_resolution` config: explicit `guards` list and optional `scan_all_guards`.
- HTTP logging in middleware `terminate()` with `response_status` and `duration_ms`; migration `2026_04_07_120000_add_response_metrics_to_event_logs_table.php`.
- `EventLog::toProviderPayload()`; `legacy_to_array_provider_payload` config to restore standard `toArray()` when set to `false`.
- Migration `2026_04_07_120001_alter_event_logs_subject_id_and_sync_index.php`: `subject_id` as string, composite index for unsynced queries.
- `EventLogTransport` contract, `AzureEventHubTransport`, container binding; SAS token caching (`cache_sas_token`, `token_cache_buffer_seconds`, `sas_ttl_seconds`).
- `persist_mode` (`sync` / `queued`), `queue.*` config, `RecordEventLogJob`, `EventLogRecorder`.

### Deprecated

- `AzureEventHubAction` (extends `AzureEventHubTransport`).

### Changed

- Model `subject_id` is stored and cast as string for UUID-friendly primary keys.
- Azure outbound body uses `toProviderPayload()`.

### Notes for upgrades

- Register `EventLogMiddleware` as terminable (normal Laravel `append` usage is sufficient).
- Run new migrations after upgrading.
