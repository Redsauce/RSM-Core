## Context

API tokens are stored in `rs_tokens` and authorize API access through `rs_token_permissions`. The existing `RS_CLIENT_ID` identifies the RSM client that owns the token and is also used to resolve item types, properties, and permissions. It does not represent the customer item that an external integration should be limited to.

Items can reference other items through `identifier` and `identifiers` properties. The new token scope must use a customer dependency property whose referred item type matches the customer item type stored on the token and whose value contains the customer item ID stored on the token.

## Goals / Non-Goals

**Goals:**
- Keep standard tokens backward compatible.
- Add a token-level customer item association without changing the token string format.
- Enforce customer scope for API v1 and v2 item create, read, count, search, update, delete, properties, audit trail, and file/image property access operations.
- Audit every API PHP endpoint and document whether it enforces customer scope, only propagates token metadata, is public/non-token, or is intentionally unchanged.
- Centralize customer-scope checks in token/item utilities so v1 and v2 endpoints use the same rules.
- Preserve existing property permission checks and user visibility checks as an additional authorization layer.

**Non-Goals:**
- Redesign token permissions or replace `rs_token_permissions`.
- Add a new authentication scheme.
- Add customer scoping to non-item API operations unless they directly fetch or mutate item-backed data such as properties, audit trail, files, or images.
- Infer customer dependency through arbitrary relationship paths beyond the direct identifier property required for this change.

## Decisions

### Store the scoped customer item type and item ID on `rs_tokens`

Add `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` as nullable unsigned integer columns on `rs_tokens`. Both columns must be `NULL` or `0` for a standard token. Both columns must be positive for a customer-scoped token. Partial scope data is invalid and must fail closed.

A scoped token can only interact with items that depend on the pair `(RS_CUSTOMER_ITEM_TYPE_ID, RS_CUSTOMER_ITEM_ID)`. Storing the item type is necessary because item IDs alone are not globally meaningful and because the authorization check must verify that the dependency property refers to the expected customer item type.

Alternative considered: reuse `RS_CLIENT_ID`. This was rejected because `RS_CLIENT_ID` already identifies the RSM tenant/client that owns the token and is required for existing permission and metadata resolution.

### Resolve customer dependency by configured identifier property

The implementation will provide a shared helper that can determine whether an item type has a customer dependency property. That property MUST be a single-value `identifier` whose referred item type equals `rs_tokens.RS_CUSTOMER_ITEM_TYPE_ID`. The helper will check item values in `rs_property_identifiers` and compare `RS_DATA` with `rs_tokens.RS_CUSTOMER_ITEM_ID`.

Alternative considered: accept `identifiers` multi-value dependencies. This is excluded from the first implementation to keep authorization semantics simple and avoid partial-match ambiguity.

### Enforce scope as an additional authorization check

Customer-scoped token checks run after token validation and before returning or mutating item data. Existing property permission checks still decide which properties can be read or written. Passing property permission without passing customer scope is not sufficient.

The implementation must centralize token customer-scope behavior in shared utilities instead of duplicating SQL and branching logic in each endpoint. `RSMtokensManagement.php` should own token metadata helpers such as detecting whether a token is customer-scoped, retrieving the `(customerItemTypeID, customerItemID)` pair, and validating partial scope data. `RSMitemsManagement.php` or a nearby item-security utility should own item-level helpers such as finding the customer dependency identifier, asserting that one item or a batch of items is inside scope, validating create payloads, and building reusable filters for list/count/search queries.

Endpoint files should only call these shared helpers at the correct operation boundary and keep endpoint-specific response formatting. This keeps global-token behavior in one place and avoids slightly different scope rules across v1, v2, binary, audit, and helper endpoints.

Every PHP endpoint under `Server/htdocs/AppController/commands_RSM/api/` must be classified during implementation:

- Item-backed endpoint: enforce customer scope before returning, mutating, queuing, exporting, or streaming item data.
- Token-management endpoint: preserve standard token behavior and include customer-scope fields where tokens are created, listed, or administered.
- Public/non-token endpoint: no scope enforcement needed, but document why it cannot expose customer data.
- Shared/helper endpoint: no direct enforcement needed if it does not process a request, but document the helper impact.

Any endpoint that accepts `RStoken` and cannot be confidently classified must fail closed for customer-scoped tokens.

Alternative considered: inject customer filters only in list endpoints. This was rejected because direct item reads, updates, deletes, creates, binary property reads, feeds, and helper endpoints would still be able to bypass the customer boundary.

### Apply different behavior per operation type

Read/list/count/search endpoints will restrict results to customer-scoped items. Direct item reads, property reads, audit trail reads, pending-action reads, feeds, trigger-driven item interactions, and file/image property downloads that target an out-of-scope item return no data or an access error according to the endpoint's existing error style. Update and delete operations fail if any requested item is out of scope, including updates to file/image properties. Create operations fail unless the payload contains the required customer dependency property with the token customer item ID and that property refers to the token customer item type.

Staff/user lookup endpoints are identity helpers rather than ordinary item reads. `RS_USER_ID` is an internal `rs_users` identifier, not an item property, while `rs_users.RS_ITEM_ID` points to the related staff item. Customer-scoped tokens should fail closed for user lookup unless the implementation can validate the linked staff item against the token customer scope. If staff items do not have the required direct customer dependency, these endpoints must not return staff item IDs or internal user IDs for customer-scoped tokens.

Alternative considered: automatically add the customer dependency during create. This was rejected because silently mutating payloads can hide integration errors and may create inconsistent items if the required property is not valid for the target item type.

### Expose customer scope in token management

Token creation and listing endpoints will accept and return the optional customer item type ID and customer item ID so administrators can create standard tokens or customer-scoped tokens. Existing callers that do not provide the new values continue creating standard disabled tokens.

## Risks / Trade-offs

- Ambiguous customer dependency configuration -> Validate that scoped item types have exactly one eligible customer identifier property before allowing access, and fail closed when the dependency cannot be determined.
- Existing customer-related data may use multi-identifiers or indirect relationships -> Keep the first implementation direct and document unsupported patterns; extend later if needed.
- Query performance can degrade for list/count endpoints -> Use indexed identifier tables and add customer dependency filters at the database-query level instead of filtering large result sets in PHP.
- Mixed standard and customer-scoped token behavior can be confusing -> Name helper functions and DB fields explicitly around customer scope and keep `RS_CLIENT_ID` semantics unchanged.

## Migration Plan

1. Add nullable `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` to `rs_tokens` in `Database/schema.sql`.
2. Add the corresponding guarded `ALTER TABLE rs_tokens ... ADD IF NOT EXISTS ...` migration to the v6.9.0.3.164 -> v7.0.0.3.165 updater flow executed by `Server/htdocs/AppController/commands_RSM/updater/server/phpUpdate_From_v6.9.0.3.164_to_v7.0.0.3.165.php`, using its `update_post.sql` payload so the change is applied to the main database and client-specific databases without failing when the columns already exist.
3. Deploy code that treats both missing, `NULL`, or `0` customer scope fields as standard token behavior.
4. Update token administration endpoints/UI callers to pass both the customer item type ID and customer item ID only when creating customer-scoped tokens.
5. Backfill no existing rows; all current tokens remain standard tokens.

Rollback is limited to disabling customer-scoped tokens or setting `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` to `NULL` before reverting code. Existing standard tokens are not affected by the schema addition.

## Open Questions

- Should customer-scoped tokens be allowed to read the customer item itself, or only items that depend on that customer item?
