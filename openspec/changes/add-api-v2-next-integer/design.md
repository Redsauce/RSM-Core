## Context

`wndFinancialDocuments_generateInvoiceDateAndID.php` finds the maximum invoice number after optionally filtering by the current year and invoice series, adds one, and writes the result. Its item type and application properties are hard-coded and its read-then-write sequence is not protected against concurrent requests.

The new API v2 operation must work with any client integer property. Because a generic integer property does not identify which sibling property stores its period, the request supplies that date property. The server derives the period from the current UTC date and writes that date together with the number. Client and user identity continue to come from the authorization token.

## Goals / Non-Goals

**Goals:**

- Calculate and write the next positive integer for a caller-selected property.
- Scope API allocations by the current UTC year, with optional series and implicit customer restrictions.
- Continue from the newest populated earlier year when the current UTC year has no numbered items.
- Write the generated number and current UTC date atomically.
- Accept numeric client property IDs and mapped application property names.
- Follow API v2 validation, permission, customer-token scope, CORS, JSON, and debug-message conventions.
- Prevent duplicate allocations between concurrent calls to this endpoint for the same sequence.
- Avoid loading all matching items into PHP memory.

**Non-Goals:**

- Changing legacy request/response contracts or numbering rules.
- Allowing callers to choose the period date or year.
- Providing arbitrary filter rules, configurable increments, prefixes, padding, or reusable released numbers.
- Enforcing uniqueness against code paths that write the integer property without using this endpoint.
- Adding a database table, index, trigger, or migration.

## Decisions

### Endpoint and request contract

Add `POST /api/v2/items/nextInteger.php`, with the existing API v2 `OPTIONS` handling. The JSON body contains:

```json
{
  "itemID": "123",
  "propertyID": "invoice.client.invoiceID",
  "filterPropertyID": "invoice.client.invoiceDate",
  "series": "A",
  "seriesPropertyID": "invoice.client.serie"
}
```

`itemID`, `propertyID`, and `filterPropertyID` are required. The filter property defines annual periods and receives `gmdate('Y-m-d')`. Callers do not supply a date or year. `series` and `seriesPropertyID` remain an optional pair; supplying only one is invalid.

This flat contract follows existing API v2 request bodies and makes the otherwise unknowable date/series properties explicit. An arbitrary `filterRules` contract remains out of scope because it broadens this narrowly defined operation and makes sequence identity harder to validate and lock.

### Property and target validation

Resolve identifiers with `ParsePID()`. Infer the item type from the target property and require:

- the target property to exist and have type `integer`;
- the required filter property to exist, belong to that same item type, and have type `date`;
- the series property to be usable as an equality filter;
- the target item to exist in that item type;
- the token/user to have the same effective write access used by API v2 item updates;
- the token/user to have effective read and write access to the filter property and read access to the optional series property;
- the target item to pass `RSitemMatchesTokenCustomerScope()`.

Re-read the target number and filter date while holding the sequence lock. Reject an item that already has a positive number or a filter date rather than silently overwriting either value. Zero or an empty/missing number remains assignable when the date is also empty.

### Scope semantics

The calculation starts with items for the resolved client, item type, target integer property, and required date filter property. It selects the current UTC year when that year has data. Otherwise it selects the newest earlier year containing numbered items. An optional series adds exact equality against the supplied value.

For a customer-scoped token, the token's customer dependency is an additional implicit scope in both the maximum query and lock key. This follows the filtering behavior of existing item-list endpoints and prevents one customer from observing or consuming another customer's sequence.

Within the selected period, the assigned value is `MAX(target property) + 1`. If neither the current nor any earlier period contains a positive value, the assigned value is `1`. Non-positive legacy values are ignored. A manual number edit affects the next allocation whenever it changes the maximum in the selected period.

### Period-maximum calculation

Use the dedicated helper in `RSMitemsManagement.php` to calculate `MAX(targetValue.RS_DATA)` in SQL and join only the supplied scope properties. For rolling API periods, group by the filter date year, order years descending, and select one group at or before the current UTC year. Do not retrieve every matching item through `IQ_getFilteredItemsIDs()`. Resolve table names exclusively from validated property types, cast identifiers to integers, and bind scope values.

This keeps PHP memory bounded and makes the result independent of item creation order.

### Concurrency and persistence

Acquire a MariaDB/MySQL named advisory lock before starting a transaction, re-reading the target values, calculating the next value, and writing both the number and UTC date with `setPropertyValueByID()`. Commit only after both properties and their audit trails have persisted. Roll back any failed calculation, property write, audit write, or commit before releasing the lock in a `finally` path. Derive the bounded rolling-period lock name from a hash of client ID, target property ID, filter property ID, series scope, and customer scope; do not split that lock by year because an empty new year consumes the previous period's maximum.

The lock serializes calls for the same logical sequence while allowing unrelated properties or scopes to proceed independently. A database uniqueness constraint would provide stronger global enforcement, but is not possible without changing the current flexible property storage model and adding a migration.

Return success only after both `setPropertyValueByID()` calls report successful writes. The response includes the target item ID, resolved integer and filter property IDs, assigned value, and UTC date.

### Implementation boundaries

Keep request parsing, authorization, orchestration, and HTTP responses in the new endpoint. API files must not contain database queries: place period-maximum calculation and advisory-lock database operations in `RSMitemsManagement.php`, which the endpoint already loads. Keep the additions focused and do not modify the database schema.

## Risks / Trade-offs

- **Writers that bypass this endpoint can still allocate the same value** → Document that atomicity covers callers of this endpoint; retain the lock until the write completes.
- **A process can fail while holding an advisory lock** → Connection closure releases the lock; use a finite acquisition timeout and a `finally` release for normal error paths.
- **Incorrect scope properties could create an unintended sequence** → Require the filter and optional series properties to resolve and belong to the target item type, and validate their types.
- **Series representations may differ between callers** → Compare the canonical value accepted/stored by RSM and cover list-backed or textual series behavior in integration tests.
- **Host timezone could select a wrong period near midnight** → Derive both the stored date and year boundary from UTC with `gmdate()`.
- **A property write can succeed before its audit write fails** → Keep both writes in the endpoint transaction and roll back before releasing the sequence lock, so a `500` never leaves the number assigned.

## Migration Plan

1. Deploy the updated `RSMitemsManagement.php` and the new endpoint alongside the migrated legacy callers.
2. Run API tests against a non-production client for current-period, previous-period fallback, optional-series, and concurrent requests.
3. Adopt the endpoint from selected clients while the legacy PHP contracts remain available.
4. Roll back by removing the new endpoint and its item-manager helpers; no schema or stored-data migration is required.

## Open Questions

- The four legacy generators now reuse the maximum helper internally.
- Strong uniqueness across every RSM writer would require a later storage-level design.

## Legacy caller migration

The four existing number generators delegate to the shared maximum helper. Internal series scopes also accept a set of equality values for the existing grouped-relation case; an empty set matches nothing, and lock keys canonicalize order and duplicates. This does not extend the API JSON contract.

RSallocateNextIntegerPropertyValue owns a transaction and the existing scope lock until the persistence callback completes. Call it outside an existing transaction. Each legacy caller rechecks and persists its own fields in the callback. The shared allocator has no date-writing parameter or number/date-specific helper. The creation caller keeps creation and scope-property persistence in the lock and verifies its number and parent were stored. Failure rolls back and releases the lock. Other metadata updates retain existing behavior.

Unscoped and scoped sequences can overlap and use distinct locks, as before; this migration does not enforce uniqueness across differently defined scopes or arbitrary writers. Invoice empty-series behavior remains unfiltered. Numbered items must belong to their scope; the API scope-assignment limitation is unchanged.
