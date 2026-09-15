## Context

`RSitemMatchesTokenCustomerScope` and `RSappendTokenCustomerScopeFilter` currently resolve a single direct dependency before authorizing an item or filtering a query. A parent without a self-reference fails that check. Batch checks delegate to the direct helper; create payloads use separate dependency helpers. API v1 and v2 use both direct checks and filtered queries, so changing only the direct helper would leave inconsistent behavior.

The existing requirements live in the pending `restricted-api-tokens` change; no canonical `openspec/specs/` exists. The new capability defines the parent-type special case, which takes precedence over dependency-only rules for that type. Other item types retain their existing rules.

## Goals / Non-Goals

**Goals:** Allow authorized operations on the exact existing parent, without self-reference; isolate all other parents; apply identical scope semantics across direct access and queries; preserve permission enforcement and related-item access.

**Non-Goals:** New permissions, schema changes, transitive relationships, changes to token scope configuration, or granting access to all parents. Creating a new parent is not covered by the existing-parent exception.

## Decisions

### 1. Resolve parent identity before dependency lookup

After authentication and scope validity checks, normalize the requested item type using the existing resolver. For the configured customer item type, require the configured customer item ID and verify the item exists with that type in the authenticated token's client. A different item of that type is denied immediately, even if it has a dependency pointing at the allowed parent. For every other type, use the existing direct identifier/multi-identifier logic.

This type-specific branch prevents a broad `identity OR dependency` rule from admitting other parents. Requiring a self-reference was rejected because the parent identity already defines membership and should not require synthetic data.

### 2. Make queries enforce the same identity constraint

The shared scope helper appends an internal `array('itemID' => <parent ID>)` constraint for the parent type. `IQ_getFilteredItemsIDs` recognizes it separately from property filters and adds `AND rs_items.RS_ITEM_ID = <parent ID>` to the base WHERE clause. The query already constrains client and item type. API callers construct property filters explicitly, so this internal entry does not add a public request field or a fabricated property.

Compose scope using AND with caller filters, visibility, and existing authorization constraints, before counting, pagination, or returning results. Filtering only after retrieval was rejected because it can leak counts and produce incorrect pages.

### 3. Preserve operation checks and validate all targets

Scope membership is one authorization condition. Keep operation and property permissions, including permissions inherited from master templates. Apply the shared rule to direct reads, mutations, file/picture access, identity lookup, and other existing item-backed routes. Every explicit batch target must pass the existing access checks before mutation. Automatic reference cleanup during deletion remains unchanged.

Deletion keeps its existing behavior: authorize the explicitly requested items and retain the existing reference cleanup performed by `deleteItem`/`deleteItems`. This change adds no access or WRITE-permission checks on other items solely because they reference an item being deleted. Empty ID lists are skipped without deleting anything; non-empty groups retain their scope and DELETE-permission checks.

Tree queries use the shared constraint, and generated path nodes, parent IDs, and child IDs are checked before inclusion because path expansion can introduce additional ancestors.

For creates of the parent type, reject the request: a new item is not the existing configured parent. Keep other-type creation and dependency handling unchanged. The exception applies to supported operations on an existing parent, including deletion only when existing permissions allow it; a subsequently missing parent fails existence checks without altering token metadata.

## Risks / Trade-offs

- Query and direct-check divergence → Cover list/count/search and direct routes in both API versions with the same fixtures.
- Same-type dependencies admitting other parents → Make exact parent identity the exclusive rule for the parent type and test a sibling linked to the allowed parent.
- Cross-client or item-type confusion → Validate the complete client/type/item identity and test matching IDs in other contexts.
- Batch or indirect writes escaping scope → Check all affected item targets before mutation or queueing using existing endpoint semantics; reject unauthorized batches without partial changes.
- Pending specs conflict during archival → Reconcile dependency definition, shared query filtering, creation, and staff lookup wording in `restricted-api-tokens` when synchronizing the two capabilities.

## Migration Plan

Implement shared helpers and affected callers together, run focused regression checks, then deploy without data migration. Existing parent access becomes available only where current permissions already permit it. Roll back the code to restore dependency-only behavior if necessary.

## Open Questions

None. The implementation audit and verification boundaries are recorded in `validation.md`.
