## Context

`wndFinancialDocuments_generateInvoiceDateAndID.php` finds the invoice number on the last numbered item after optionally filtering by the current year and invoice series, adds one, and writes the result. Its item type and application properties are hard-coded and its read-then-write sequence is not protected against concurrent requests.

The new API v2 operation must work with any client integer property. Because a generic integer property does not identify which sibling properties contain a date or series, a scoped request must provide both the scope value and the corresponding property identifier. Client and user identity continue to come from the authorization token.

## Goals / Non-Goals

**Goals:**

- Calculate and write the next positive integer for a caller-selected property.
- Support no scope, a year scope, a series scope, or their intersection.
- Accept numeric client property IDs and mapped application property names.
- Follow API v2 validation, permission, customer-token scope, CORS, JSON, and debug-message conventions.
- Prevent duplicate allocations between concurrent calls to this endpoint for the same sequence.
- Avoid loading all matching items into PHP memory.

**Non-Goals:**

- Changing legacy request/response contracts or numbering rules.
- Updating the date or series property on the target item.
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
  "year": 2026,
  "yearPropertyID": "invoice.client.invoiceDate",
  "series": "A",
  "seriesPropertyID": "invoice.client.serie"
}
```

`itemID` and `propertyID` are required. `year` and `yearPropertyID` form an optional pair, as do `series` and `seriesPropertyID`. Omitting both pairs produces one sequence across all items of the inferred item type. Supplying only one member of a pair is invalid.

This flat contract follows existing API v2 request bodies and makes the otherwise unknowable date/series properties explicit. An arbitrary `filterRules` contract was considered, but rejected because it broadens this narrowly defined operation and makes sequence identity harder to validate and lock.

### Property and target validation

Resolve identifiers with `ParsePID()`. Infer the item type from the target property and require:

- the target property to exist and have type `integer`;
- each supplied scope property to exist and belong to that same item type;
- the year property to have type `date` or `datetime`;
- the series property to be usable as an equality filter;
- the target item to exist in that item type;
- the token/user to have the same effective write access used by API v2 item updates;
- the token/user to have effective read access to each supplied scope property;
- the target item to pass `RSitemMatchesTokenCustomerScope()`.

Re-read the target value while holding the sequence lock. Reject an already assigned positive value rather than silently renumbering it. Zero or an empty/missing value remains assignable, matching the invoice-generation behavior.

### Scope semantics

The last numbered item is selected only for the resolved client, item type, and target property. Optional scopes add:

- year: date/datetime values from `YYYY-01-01 00:00:00` inclusive to the next year exclusive;
- series: exact equality against the supplied series value;
- both: intersection of the two conditions.

For a customer-scoped token, the token's customer dependency is an additional implicit scope in both the last-item query and lock key. This follows the filtering behavior of existing item-list endpoints and prevents one customer from observing or consuming another customer's sequence.

No matching positive value means the assigned value is `1`. Non-positive legacy values are ignored. Manual edits to an older item do not change the sequence; the value on the last matching item determines the next value.

### Last-item calculation

Add a dedicated helper to `RSMitemsManagement.php` that selects the value from the highest `RS_ITEM_ID` in the integer property table and joins only the supplied scope properties. Do not retrieve every matching item through `IQ_getFilteredItemsIDs()`. Resolve table names exclusively from validated property types, cast identifiers to integers, and bind request-derived scope values.

This is both more memory-efficient and preserves the intended sequence after manual number edits.

### Concurrency and persistence

Acquire a MariaDB/MySQL named advisory lock before starting a transaction, re-reading the target value, calculating the next value from the last matching item, and calling `setPropertyValueByID()`. Commit only after the property and its audit trail have both persisted. Roll back any failed calculation, property write, audit write, or commit before releasing the lock in a `finally` path. Derive the bounded lock name from a hash of client ID, target property ID, year scope, and series scope.

The lock serializes calls for the same logical sequence while allowing unrelated properties or scopes to proceed independently. A database uniqueness constraint would provide stronger global enforcement, but is not possible without changing the current flexible property storage model and adding a migration.

Return success only after `setPropertyValueByID()` reports a successful write. The response includes the target item ID, resolved property ID, and assigned value.

### Implementation boundaries

Keep request parsing, authorization, orchestration, and HTTP responses in the new endpoint. API files must not contain database queries: place last-item calculation and advisory-lock database operations in `RSMitemsManagement.php`, which the endpoint already loads. Keep the additions focused and do not modify the database schema .

## Risks / Trade-offs

- **Writers that bypass this endpoint can still allocate the same value** → Document that atomicity covers callers of this endpoint; retain the lock until the write completes.
- **A process can fail while holding an advisory lock** → Connection closure releases the lock; use a finite acquisition timeout and a `finally` release for normal error paths.
- **Incorrect scope properties could create an unintended sequence** → Require scope properties to resolve and belong to the target item type, and validate their types.
- **Series representations may differ between callers** → Compare the canonical value accepted/stored by RSM and cover list-backed or textual series behavior in integration tests.
- **Year boundary handling can differ for datetime values** → Use an inclusive lower bound and exclusive next-year bound.
- **A property write can succeed before its audit write fails** → Keep both writes in the endpoint transaction and roll back before releasing the sequence lock, so a `500` never leaves the number assigned.

## Migration Plan

1. Deploy the updated `RSMitemsManagement.php` and the new endpoint alongside the migrated legacy callers.
2. Run API tests against a non-production client for all four scope combinations and concurrent requests.
3. Adopt the endpoint from selected clients while the legacy PHP contracts remain available.
4. Roll back by removing the new endpoint and its item-manager helpers; no schema or stored-data migration is required.

## Open Questions

- The four legacy generators now reuse the last-item helper internally.
- Strong uniqueness across every RSM writer would require a later storage-level design.

## Legacy caller migration

The four existing number generators delegate to the shared last-item helper. Internal series scopes also accept a set of equality values for the existing grouped-relation case; an empty set matches nothing, and lock keys canonicalize order and duplicates. This does not extend the API JSON contract.

RSallocateNextIntegerPropertyValue owns a transaction and the existing scope lock until the persistence callback completes. Call it outside an existing transaction. Each legacy caller rechecks and persists its own fields in the callback. The shared allocator has no date-writing parameter or number/date-specific helper. The creation caller keeps creation and scope-property persistence in the lock and verifies its number and parent were stored. Failure rolls back and releases the lock. Other metadata updates retain existing behavior.

Unscoped and scoped sequences can overlap and use distinct locks, as before; this migration does not enforce uniqueness across differently defined scopes or arbitrary writers. Invoice empty-series behavior remains unfiltered. Numbered items must belong to their scope; the API scope-assignment limitation is unchanged.
