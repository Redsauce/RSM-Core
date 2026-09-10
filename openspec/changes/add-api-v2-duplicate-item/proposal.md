## Why

RSM needs an API operation to duplicate an item of any configured type. The existing document-specific command demonstrates reuse of `duplicateItem()`, but its document fields and related-concept rules must not define generic duplication behavior.

## What Changes

- Add `POST /api/v2/items/duplicate.php` accepting one source `itemTypeID` and `itemID`, returning the new item ID.
- Duplicate one item in the same client and item type using the shared item manager.
- Copy properties according to RSM configuration: exclude every property marked `RS_AVOID_DUPLICATION`, without document-specific resets or automatically generated numbers/dates.
- Copy eligible relation values as references; do not recursively duplicate related items or descendants.
- Enforce existing API v2 authentication, effective READ/CREATE permissions, and customer-token scope. Reject unauthorized requests rather than silently producing a partial copy.
- Return success only after the complete duplication is persisted; roll back failed copies.
- Preserve existing PHP callers and the separate next-integer endpoint.

## Capabilities

### New Capabilities

- `api-v2-duplicate-item`: Authenticated generic single-item duplication respecting configured non-duplicable properties.

### Modified Capabilities

None.

## Impact

- New endpoint under `Server/htdocs/AppController/commands_RSM/api/v2/items/`.
- Reuses `RSMitemsManagement.php`, `duplicateItem()`, property metadata and API token/validation helpers.
- Focused shared-helper changes may be needed to propagate copy failures reliably and support transactional use without changing legacy success behavior.
- Adds local endpoint and shared-helper regression coverage; no database schema change.
