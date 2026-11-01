## Why

Large item reads can exceed request execution limits even when variant values are written to temporary files. Clients need to retrieve bounded pages without changing existing integrations or response formats.

## What Changes

- Add optional, zero-based `offset` to the Data Manager item-list endpoint and API v1 item-list endpoint; keep `limit` as the maximum number of returned items.
- Add optional `limit` and `offset` to API v2 `items/get.php`, which currently ignores pagination and passes an empty limit to the shared reader.
- Preserve existing calls without the new pagination parameters, including legacy limit handling, response envelopes, property naming, permissions and token scopes.
- Provide deterministic ordering for opted-in pagination, apply pages to filtered and authorized results, and load only page properties when no external filters require materialization.
- Add parameter comments and complete request examples directly in all three endpoint files, plus shared-helper documentation, so source-reading AI agents can discover the feature.

## Capabilities

### New Capabilities

- `item-read-pagination`: Backward-compatible offset/count pagination, validation, ordering, filtering and discoverable source examples across the three item-list read endpoints.

### Modified Capabilities

None.

## Impact

- `Server/htdocs/AppController/commands_RSM/shared/classDataManager_getItems.php`
- `Server/htdocs/AppController/commands_RSM/api/api_getItems.php` (API v1)
- `Server/htdocs/AppController/commands_RSM/api/v2/items/get.php` (API v2, existing GET/POST JSON-body contract)
- Shared item query/pagination helpers in `utilities/RSMitemsManagement.php`, with validation helpers if needed.
- Focused regression tests for endpoint forwarding, SQL pagination, permissions, output formats and old callers.
- No schema migration, new response envelope, compression change, deployment or modification of unrelated GET endpoints is included.
