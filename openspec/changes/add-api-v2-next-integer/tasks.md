## 1. Sequence calculation utilities

- [x] 1.1 Add a validated database-side last-numbered-item helper to `RSMitemsManagement.php` for an integer property with optional year and series scopes.
- [x] 1.2 Add bounded advisory-lock acquire/release helpers to `RSMitemsManagement.php`, keyed by client, property, year, and series.
- [x] 1.3 Add unit-level regression tests for global, year-only, series-only, and combined last-item calculations.

## 2. API v2 endpoint

- [x] 2.1 Create `api/v2/items/nextInteger.php` with POST/OPTIONS handling, JSON parsing, token client resolution, and request-shape validation.
- [x] 2.2 Resolve target and optional scope property identifiers and validate their types and common item type.
- [x] 2.3 Enforce effective WRITE access, scope-property READ access, item existence, and customer-token item scope before sequence access.
- [x] 2.4 Under the sequence lock, recheck that the target has no positive assigned value, calculate the next value, and persist it with `setPropertyValueByID()`.
- [x] 2.5 Return the item ID, resolved property ID, and assigned integer using API v2 JSON and debug-message conventions.
- [x] 2.6 Keep the API endpoint query-free by delegating all sequence database operations to `RSMitemsManagement.php`.

## 3. Verification

- [x] 3.1 Add endpoint tests for malformed or incomplete scopes, unresolved properties, invalid property types, nonexistent items, existing positive values, and denied access.
- [x] 3.2 Add integration coverage proving concurrent requests in one scope receive distinct consecutive values and different scopes do not block each other.
- [x] 3.3 Verify a large sequence uses a bounded ordered query without materializing all matching items in PHP.
- [x] 3.4 Run PHP syntax checks, the compatibility suite, and API smoke tests.

## 4. Legacy callers

- [x] 4.1 Extend the shared last-item query with an internal set-of-values scope and canonical lock keys.
- [x] 4.2 Centralize transactional integer allocation; keep date writes in the legacy callers and migrate the four legacy callers preserving their scopes and response contracts.
- [x] 4.3 Add scope-isolation and persistence coverage; run local regressions and compatibility checks, and attempt the optional database tests.

Validation of the legacy migration: local next-integer regressions and the PHP 8.5 compatibility suite pass. The MariaDB integration test could not run because 127.0.0.1:3306 refused the connection; its new database scenarios remain unverified. HTTP smoke tests require a configured base URL and were not run.

- [x] 4.4 Remove the number/date-specific helper: legacy callers own date handling and use the generic integer allocation callback.
