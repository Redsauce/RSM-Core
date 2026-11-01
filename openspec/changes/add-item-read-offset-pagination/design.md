## Context

Data Manager `shared/classDataManager_getItems.php` and API v1 `api/api_getItems.php` forward `limit` to `getFilteredItemsIDs()`. The ID-only query currently appends `LIMIT <limit>`; Data Manager accepts the historical SQL limit string, while v1 validates decimal digits. API v2 `api/v2/items/get.php` explicitly passes an empty limit and does not consume pagination parameters.

The shared reader selects IDs before hydrating properties. This is the correct place to bound a page before loading large variants. File output already bounds memory for variant reads; v2 subsequently constructs its existing JSON response. External filters are evaluated after hydration and cannot in general be pushed into the ID-only SQL query.

Development diagnostics of a large variant request returned 1,182 items and approximately 1.24 GB of XML. This motivates smaller requests, without promising a fixed execution time or hard byte limit per page.

## Goals / Non-Goals

**Goals:**
- Consistent zero-based `offset` and maximum-count `limit` semantics on the three item-list endpoints.
- Preserve historical behavior unless the caller opts into the new pagination contract.
- Select page IDs before property hydration wherever filtering allows it.
- Make the feature discoverable through source comments and realistic, credential-free examples.

**Non-Goals:**
- Pagination of files, pictures, metadata, audit records, counts, trees or single-item endpoints.
- New total-count/next-page response envelopes, automatic page-size caps, cursors, snapshots, compression tuning or server timeout changes.
- Deployment or changes to the previous variant-spooling work.

## Decisions

### 1. Add optional offset and reuse limit as a count

`offset=N` skips N entries in the authorized, filtered, ordered result. `limit=M` returns at most M entries, not an inclusive ending position or item ID. Positions are one-based only in explanatory examples; offset is zero-based.

- `offset=0&limit=100`: positions 1 through 100.
- `offset=100&limit=100`: positions 101 through 200.
- `offset=1&limit=499`: positions 2 through 500.
- `offset=999&limit=1`: position 1,000, irrespective of the item's actual ID.

For Data Manager/v1, an omitted or empty form `offset` leaves the historical limit-only path unchanged. A nonempty offset activates the new contract and requires a nonempty limit. For v2, the presence of `limit` or `offset` activates pagination; `limit` alone defaults offset to zero, while offset requires limit. No parameters means the original unpaginated path.

New-mode values accept integers and unsigned decimal integer strings within PHP's supported integer range. Zero is valid for both: limit zero returns an empty page. Reject negative, fractional, boolean, array/object, null, whitespace-only, exponent, SQL-fragment and overflowing values. JSON empty strings are invalid. Do not silently cast bad input to zero. Return the established endpoint-specific validation error (HTTP 400 for APIs; the established Data Manager error record).

The new contract avoids overloading limit with `offset,count`; retain legacy Data Manager limit strings only when offset is absent/empty. V1 continues rejecting composite limit strings. Existing limit-only calls retain their previous validation and order.

### 2. Extend shared helpers without changing existing callers

Append an optional pagination argument to shared helpers, or use an equivalent explicit options structure with an absent/default marker. Preserve all existing positional arguments, named argument names and defaults. Avoid global request state in the shared SQL builder. Build SQL offset/count only from validated integers; never concatenate the raw new parameter.

For requests without external filters, apply filtering, explicit IDs, tenant/token scope and ordering to the ID query, then SQL `LIMIT count OFFSET offset`. Hydrate, translate and serialize only the selected IDs. Do not fetch all properties and apply `array_slice()` afterward.

The offset is a result position, not a primary-key seek. SQL may still scan or sort preceding matches; it must not hydrate their large property values in PHP. A caller that already knows an item ID should use existing `IDs` lookup. Cursor pagination is an alternative for very deep pages, deferred from this change.

### 3. Stabilize ordering only for opted-in requests

Default new-mode ordering to `rs_items.RS_ITEM_ID ASC`. If an existing supported property order is supplied, preserve its semantics and add item ID ascending as the final unique tie-breaker. V2 uses the ID default; introducing an unrelated v2 orderBy feature is out of scope. Do not alter no-offset legacy ordering. Repeated pages are stable for an unchanged result set, not a snapshot across edits.

### 4. Preserve external filter semantics

In new mode with `extFilterRules`, use an unbounded, deterministically ordered candidate query; hydrate and run existing external filters first, then slice once by offset/count, then serialize using the existing endpoint format. Do not let the internal file path apply filters twice or slice the SQL candidates before external filtering. Preserve the original path for calls without new pagination.

This materialization fallback may remain expensive and memory intensive. Document it in the endpoint comments rather than promising the page-only hydration optimization for external filters. Limit zero can return the normal empty shape after normal validation and authorization without candidate hydration.

### 5. Preserve wire formats and authorization

Keep the same XML recordsets, compression conventions and file cleanup for Data Manager/v1, and the same JSON layout/empty-result representation for v2, including categories, system names, translated IDs and unresolved placeholders. No metadata wrapper or mandatory count query is added. Keep existing HTTP methods, token permissions and customer-scope restrictions.

### 6. Document in the code where callers discover endpoints

Update top-of-file parameter comments and add credential-free, copyable examples in all three endpoint files. V1/Data Manager examples use their existing form field names; v2 examples use valid JSON bodies accepted by its existing GET/POST contract, such as `{"itemTypeID":"23","offset":100,"limit":100}`. Explain initial/next pages, positions 2-500, offsets versus IDs, maximum count, short/empty pages, stable order, changing-data caveat and external-filter cost. Add a shared-helper docblock explaining opt-in arguments and SQL-before-hydration behavior. Comments describe implemented functionality once applied, not a proposed feature.

## Risks / Trade-offs

- [Deep offsets may require SQL scans/sorts] -> Use existing IDs lookup when the ID is known; document that offset is not a constant-time seek.
- [Concurrent inserts, deletes or order/filter changes shift pages] -> Stable unique order prevents tie ambiguity for unchanged data; explicitly document absence of snapshot guarantees.
- [Large individual variants still produce large pages] -> Recommend initial pages of 50-100 and allow callers to tune smaller; do not impose a new cap on old integrations.
- [External filters require full materialization] -> Keep correct post-filter pagination and document the performance limitation.
- [Legacy raw limit syntax or return ordering could change accidentally] -> Keep a separate absent-offset path and regression-test old calls.
- [Variants written to a file are reread for v2 JSON] -> Page before hydration to bound that existing conversion; streaming JSON is out of scope.

## Migration Plan

Implement shared behavior and endpoint adapters with regression checks, then document examples in those same files. No database migration or client migration is required. Existing clients keep their request/response contracts. New clients adopt offset/count explicitly. Deployment is a separate action; rollback restores affected PHP files and tests without data changes.

## Open Questions

None required before implementation. Page-size tuning and any future cursor contract remain separate work.
