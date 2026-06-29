## Why

Current API tokens can authenticate and authorize access by property permissions, but they cannot limit item operations to records that belong to a specific customer represented inside the item data. This change adds client-scoped API tokens so integrations for API v1 and v2 can safely operate only on items that depend on the customer ID associated with the token.

## What Changes

- Add a client-token scope to `rs_tokens` through new nullable customer item type and customer item association columns.
- Add a token alias field and a classic `classLbxTokens_editToken.php` token-management endpoint so operators can update an existing token's customer scope and alias after creation.
- Treat existing tokens without a customer association as standard API tokens with current behavior.
- Review every API PHP endpoint and restrict all item-backed operations, including properties, audit trail, file/image property access, feeds, pending actions, and trigger-driven item interactions, for customer-associated tokens to items whose customer dependency identifier or multi-identifier points to the token customer item type and item ID.
- Apply the customer dependency restriction in addition to existing token permission checks for CREATE, READ, WRITE, and DELETE.
- Ensure create operations only succeed when the item payload establishes the required customer dependency.
- Ensure read/list/count/search operations, property reads, audit trail reads, and file/image downloads only return data from matching items.
- Ensure identity lookup endpoints such as staff/user do not leak internal user or staff item IDs to customer-scoped tokens unless explicitly allowed by customer scope.
- Ensure update/delete operations reject item IDs outside the token customer scope.

## Capabilities

### New Capabilities
- `restricted-api-tokens`: Defines token-level customer scoping for API item access in v1 and v2.

### Modified Capabilities

## Impact

- Database schema: `rs_tokens` gains customer item type and customer item association columns for client-token scoping.
- Database updater: `Server/htdocs/AppController/commands_RSM/updater/server/phpUpdate_From_v6.9.0.3.164_to_v7.0.0.3.165.php` and its `update_post.sql` payload must apply the schema change to the main database and client-specific databases.
- Token management: all `classLbxTokens_*` endpoints used by the RSM client must support configuring, displaying, enabling, disabling, and deleting tokens with customer item type and customer item associations.
- Token editing: `api/classLbxTokens_editToken.php` must infer the client from the selected token, authenticate the supplied login/password against that client, and update only the provided customer scope and alias fields.
- All API PHP endpoints under `Server/htdocs/AppController/commands_RSM/api/`, including v1, v2, token-management, feed, trigger, public, and helper endpoints.
- Shared token/item utility functions in `Server/htdocs/AppController/commands_RSM/utilities/`.
- Tests or API verification fixtures covering standard tokens and customer-scoped tokens.
