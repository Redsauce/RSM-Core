## Why

`getFilteredItemsIDs()` builds one dynamic SQL query that both filters items and joins every requested return property. Large item requests therefore add many joins even when the return properties are only needed after the matching IDs are known, increasing optimizer cost and risking MariaDB/MySQL join limits.

## What Changes

- Split dynamic item reads into two phases where possible:
  - first select matching item IDs using only item and filter joins;
  - then hydrate requested return properties in batches for those IDs.
- Preserve the public response shape and existing filter/order/translation behavior.
- Keep the existing single-query path for cases where order-by semantics require property data during filtering.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. This is an internal performance refactor with no intended API behavior change.

## Impact

- Affects `Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php`.
- Uses existing property-table helpers and `getItemsPropertyValues()` style batched reads.
- Verification must include PHP syntax/compatibility tests and focused regression coverage for item list output shape.
