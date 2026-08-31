## Why

The optimized two-phase item read path repeatedly asks for stable property metadata such as property type, default value, and main property ID while building the ID query and hydrating return properties. Repeating those lookups inside one item-list call adds avoidable database work.

## What Changes

- Add a local metadata cache scoped to each `getFilteredItemsIDs()` call.
- Reuse cached metadata between the ID-only query phase and the hydration phase.
- Keep the cache non-persistent and non-configurable, so it cannot leak across requests or environments.
- Add tests that prove repeated metadata lookups are collapsed while preserving existing responses.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. This is an internal performance refactor with no intended API behavior change.

## Impact

- Affects `Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php`.
- Affects dynamic item join regression/benchmark scripts.
- No database schema or endpoint contract changes.
