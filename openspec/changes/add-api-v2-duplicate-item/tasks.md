## 1. Shared duplication behavior

- [x] 1.1 Verify and reuse the configured eligible-property metadata list; ensure excluded values are neither copied nor explicitly defaulted.
- [x] 1.2 Make shared duplication report item, property and allocation-metadata write failures reliably without changing existing successful call signatures.
- [x] 1.3 Verify safe text/binary/missing-value copying and fresh internal ID allocation; keep required corrections in the shared item manager.

## 2. API endpoint

- [x] 2.1 Add duplicate.php with POST/OPTIONS, JSON body validation, parseITID resolution, token client/user resolution and source existence checks.
- [x] 2.2 Validate effective READ/CREATE access on eligible properties, the no-eligible-properties permission anchor, and source/destination customer scope before writing.
- [x] 2.3 Execute one non-recursive shared copy in a transaction, handle failure/rollback and allocation conflicts, and return the specified numeric IDs only after commit.
- [x] 2.4 Keep property-selection rules configuration-driven and reject unsupported request fields; preserve legacy endpoint contracts.

## 3. Verification

- [x] 3.1 Add behavioral API tests for malformed requests, unsupported fields, type resolution, missing sources, permissions, customer scope and response/error contracts.
- [x] 3.2 Add local behavioral tests for excluded values/defaults, all-excluded types, copied numbers/dates, zero/empty/Unicode/quoted values, file/image data and missing binary values.
- [x] 3.3 Test client/type isolation with colliding item IDs, unchanged source/related items and preserved reference values/order without descendant creation.
- [x] 3.4 Test rollback on property/counter failures, repeated successful requests and allocation collisions without overwrites.
- [x] 3.5 Run PHP syntax/compatibility checks, local endpoint tests and legacy duplication regression checks; record any unavailable integration environment.

## Validation results

- `php scripts/test_duplicate_item.php`: passed; executes the real endpoint and shared copy function with a transactional in-memory SQL adapter.
- `php scripts/php_compatibility_suite.php`: passed, including lint of 289 PHP files and all local regression scripts.
- HTTP smoke tests were not run because no base URL is configured.
- No commit or deployment performed.
