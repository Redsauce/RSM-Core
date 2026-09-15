## Context

The reference `financialDocuments/wndFinancialDocuments_duplicateDocument.php` calls `duplicateItem()` and then resets named document fields and duplicates related concepts. Only its use of the shared copy primitive applies here. RSM item identity is the tuple client, item type, item ID; item ID alone is insufficient.

`duplicateItem()` already selects metadata through `getClientItemTypeProperties($itemTypeID, $clientID, 1)`, whose category lookup restricts `RS_AVOID_DUPLICATION = 0`. It copies typed property storage, including binary data and reference ordering. However, it does not itself authorize API callers, and some property-write and counter-update query results are currently unchecked.

## Goals / Non-Goals

**Goals:**
- Expose generic single-item duplication through API v2.
- Make RSM's configured duplication flag the sole property-copy selection rule.
- Reuse the shared duplication primitive and existing token/client conventions.
- Validate access before copying and avoid partial persisted copies on failure.

**Non-Goals:**
- Duplicating descendants, concepts, related items, or whole graphs.
- Multiple source items, multiple copies, property overrides, or caller-supplied exclusion lists.
- Resetting dates, generating business numbers, applying series/year globals, or invoking nextInteger.
- Changing the existing document-specific endpoint or introducing a database schema.

## Decisions

### Request and response

Add `POST /api/v2/items/duplicate.php` with the standard OPTIONS/CORS handling and JSON request:

```json
{"itemTypeID": "98", "itemID": "123"}
```

`itemTypeID` resolves through `parseITID()` for the token client, accepting the same numeric or mapped identifiers as existing APIs. `itemID` is one positive integer (JSON integer or digit string), not a list. Client and user identity come exclusively from authentication. Unsupported fields, including copy counts and descendants, are rejected to avoid implying unsupported behavior.

Successful HTTP 200 response:

```json
{"itemTypeID": 98, "sourceItemID": 123, "newItemID": 124}
```

The example new ID is illustrative, not a promise of source ID plus one. Allocate a fresh internal ID through the existing item identity mechanism. Repeating a successful request creates another independent copy; this operation is not idempotent.

Use existing API authentication and debug-error conventions. Invalid body/type identifiers return 400, missing source 404, insufficient access/scope 403, and failed persistence 500. Return 200 only after commit. Preflight creates nothing.

### Property semantics

Read the configured eligible-property list for the resolved client/type. Call `duplicateItem($itemTypeID, $itemID, $clientID)` with one source, default one copy and no descendants. Share the validated metadata list with duplication where appropriate so permission checks and copied properties agree.

Only properties allowed by `RS_AVOID_DUPLICATION = 0` are copied. Excluded properties receive no value row from this operation, including no explicit default initialization; subsequent reads follow RSM's normal missing-value semantics. They are never copied and then cleared. An eligible property retains its source value regardless of its name or business meaning, including numbers, dates and series. No numbered-property exception is added.

Eligible identifier and identifiers properties keep their references and order. They do not cause target items to be copied or references to be remapped. File/image values retain their binary content and metadata using shared storage handling; absent binary values must not cause malformed inserts. No special document behavior is inherited.

### Authorization and customer isolation

Apply API v2 token validation and source existence/customer checks before copying. Require effective READ and CREATE access for every eligible property, following the existing token-permission or user-visibility conventions. If any required access is missing, reject the complete request; permission filtering must not silently alter the configured copy. Excluded properties do not require copying permissions. If no properties are eligible, require effective type-level access represented by the existing main-property READ/CREATE checks before creating the empty item; reject if no usable permission anchor exists.

A customer-scoped token must be allowed to access the source, and the copy must remain within its authorized scope. Copy the eligible dependency reference unchanged when it satisfies the existing customer rules. If exclusions would remove the dependency needed to keep the copy in scope, reject before creation rather than overriding the non-duplication flag or silently adding that property. Validate any additional dependency constraints using the existing customer-scope helpers.

### Persistence and shared code boundaries

The endpoint handles validation and JSON only; duplication SQL stays in RSMitemsManagement.php. Execute duplication and allocation metadata updates in a transaction, propagate every failed write as failure, and roll back before sending an error. Check the scalar returned new ID is positive.

Review the shared helper's unchecked property INSERT and item-type counter UPDATE results. Make focused changes so the API can detect errors reliably, retaining existing signatures and successful behavior for older callers. Do not introduce a second property-copy implementation in the API. Handle allocation conflicts through the existing identity mechanism with bounded retry or a clean rolled-back failure; never reuse an existing item ID or overwrite an existing item. Clear request-local creation bookkeeping for a rolled-back copy where applicable.

## Risks / Trade-offs

- Shared helper behavior affects older callers -> limit changes to failure propagation and required copy correctness; run existing duplication regressions.
- Missing or binary values and quoted text may expose weaknesses in legacy SQL -> cover these in local behavioral fixtures, keeping fixes in shared storage code.
- Customer dependency excluded from duplication -> reject scoped-token requests instead of bypassing configuration.
- Business numbers can be copied if not excluded -> this is intentional; administrators select non-duplicable properties in RSM.
- Other concurrent item writers can contend for internal IDs -> test contention and ensure rollback on conflicts; no global allocation redesign in this change.

## Migration Plan

Deploy the new endpoint with any narrowly required shared-helper changes after regression checks. Existing callers retain their interfaces. Rollback consists of removing the endpoint and reverting its associated shared changes if needed; no stored-data migration is required.

## Open Questions

None blocking this proposal. Single-item, non-recursive copying and omitted excluded values are the proposed initial contract.

## Implementation notes

The endpoint passes the authorized property metadata into the existing duplicateItem cache argument. Metadata helpers accept an optional failOnError flag so the API fails closed on configuration read errors; existing callers keep their default behavior. A transaction locks the item-type allocation row via RSlockItemTypeForDuplication before copying. Duplicate allocation conflicts fail cleanly and roll back.

Property copying now uses INSERT ... SELECT in the shared helper, preserving stored binary content, reference ordering and raw values without PHP string escaping or conversions. Missing source rows remain missing. Every property insert and counter update is checked; the counter update never decreases its existing value. Legacy batch/descendant parameters retain their signatures.
