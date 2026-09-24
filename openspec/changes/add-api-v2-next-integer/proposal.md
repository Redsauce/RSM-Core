## Why

RSM currently generates sequential invoice numbers through a feature-specific PHP endpoint with hard-coded application properties. API clients need a reusable API v2 operation that can calculate and assign the next value of any client `integer` property, optionally within year and series scopes.

## What Changes

- Add an authenticated API v2 endpoint that accepts a target item, an integer property, and a date filter property; it writes both the next number and the current UTC date.
- Calculate one more than the maximum number in the current UTC year. If that period is empty, continue from the newest earlier year containing data; optional series and customer scopes further restrict the set.
- Accept client property IDs or application property names and validate that target and scope properties belong to the same item type.
- Reject invalid property types, nonexistent items, unauthorized writes, customer-scope violations, incomplete scope definitions, and attempts to replace an already assigned positive number.
- Serialize concurrent requests for the same sequence scope so two API calls cannot receive the same next number.
- Migrate the four legacy number generators to the shared calculation, preserving request/response contracts and scope rules; protect number/date writes with a transaction and sequence lock.

## Capabilities

### New Capabilities

- `api-v2-next-integer`: Calculate and atomically assign the next integer property value, with optional year and series scoping.

### Modified Capabilities

None.

## Impact

- Adds a new endpoint under `Server/htdocs/AppController/commands_RSM/api/v2/items/`.
- Reuses existing request validation, token/client resolution, property metadata, item validation, customer scoping, and `setPropertyValueByID()` infrastructure.
- Adds reusable sequence locking and last-numbered-item queries to `RSMitemsManagement.php`; the API endpoint only validates and orchestrates calls to the item manager.
- Adds regression coverage for maximum calculation, UTC-date assignment, previous-period fallback, series/customer scopes, authorization, validation, and concurrency behavior.
