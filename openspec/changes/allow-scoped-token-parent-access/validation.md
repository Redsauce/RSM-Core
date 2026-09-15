## Implementation audit

- Direct API v1/v2 item reads, updates, property reads, file/picture downloads, and audit routes use `RSitemMatchesTokenCustomerScope` or its batch variant. Existing permission checks remain in place.
- Staff/user lookups delegate through `RSstaffItemMatchesTokenCustomerScope`, which now accepts the exact parent identity when the parent is a staff item.
- Lists, counts, related-item lookup queries, and pending-action queries use `RSappendTokenCustomerScopeFilter` and `getFilteredItemsIDs`. RSS delegates to `api_getItems.php` through `RSMfeeds.php`.
- Tree destination queries now use the shared scope helper; generated ancestor nodes and parent/child IDs are filtered as well.
- API v1 single/batch delete and API v2 delete authorize explicitly requested items using the existing scope and DELETE-permission checks. Empty ID lists are skipped without calling the deletion helper. Batch checks run before the mutation loops.
- The additional checks on items referencing a deleted item have been removed. Deletion keeps its existing automatic reference cleanup and does not require new WRITE permissions on those other items.
- Named URL triggers in `api/api.php` keep the existing explicitly documented non-item request exemption; configured automation execution is outside this change.
- `api_getItem.php` initializes an empty result so an authorized-scope request with no readable properties produces an empty response without an undefined-variable warning.
- The pending `restricted-api-tokens` spec and design are reconciled with the parent exception. Neither change is archived by this implementation.

## Automated verification

`scripts/test_scoped_token_parent_access.php` loads production scope/token/query functions and executes API endpoint bodies against an in-memory SQLite database. It uses adapters for bootstrap, response termination, metadata lookup, and mutation/media I/O. The production permission resolution is exercised for standalone and master-inheriting tokens.

Command used on this workstation:

```powershell
php -d extension_dir=C:/php-8.5.0/ext -d extension=sqlite3 -d extension=mbstring scripts/test_scoped_token_parent_access.php
```

- 160 checks passed: exact identity, missing parent, same IDs in other types/clients, invalid scope, disabled master, dependencies, creation, list/count/search filters, OR isolation, pagination, parent and sibling operations, file/image reads and writes, batch rejection, successful empty-list no-ops and mixed empty/non-empty groups, unchanged deletion authorization when other items reference the parent, and tree path isolation.
- `php scripts/test_master_token_templates.php` passed.
- PHP syntax checks passed for all changed PHP files and the new regression script.
- Strict OpenSpec validation passed for both affected changes.
- `git diff --check` passed.

## Verification boundaries

No live HTTP server, production MySQL database, binary cache/media service, or configured automation was invoked. SQL filtering runs against SQLite with a compatible `FIND_IN_SET` adapter; mutation calls are recorded to verify authorization and absence of partial effects. Actual database writes, binary rendering, and concurrent requests are not integration-tested by this harness. No deployment or database migration is part of this change.
