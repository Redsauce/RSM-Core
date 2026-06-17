## Why

API tokens can now store customer scope metadata and a human-readable alias, but there is no dedicated API endpoint to assign or update those values after a token exists. Operators need a server-side action consistent with the existing `classLbxTokens_*` endpoints to edit a token's associated customer item type ID, item ID, and alias while validating that the supplied login/password belongs to the client inferred from the selected token.

## What Changes

- Add a `classLbxTokens_editToken.php` API handler under `Server/htdocs/AppController/commands_RSM/api`.
- Accept a token, login/password authentication data, optional `itemTypeID`/`itemID` scope values, and an optional token alias from the request.
- Infer the client from the selected token; the request does not need to send `clientID`.
- Validate that login/password are valid for the inferred token client before updating the token.
- Persist included scope values into `rs_tokens.RS_CUSTOMER_ITEM_TYPE_ID` and `rs_tokens.RS_CUSTOMER_ITEM_ID`. Persist `RS_TOKEN_ALIAS` only when an alias is included in the request.
- Do not update a field when the request does not include new content for that field.
- If `itemTypeID`, `itemID`, and `tokenAlias` are all omitted, perform no token update.
- Reuse the existing RSM API authentication/bootstrap pattern used by sibling token-management endpoints.
- Return an `OK`/`NOK` response matching the existing token management endpoints.

## Capabilities

### New Capabilities
- `token-scope-edit-api`: API support for editing an existing token's customer item scope and alias.

### Modified Capabilities

## Impact

- `Server/htdocs/AppController/commands_RSM/api/classLbxTokens_editToken.php`
- Token-management helpers loaded through `Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php`
- `rs_tokens` table columns introduced by `Server/htdocs/AppController/commands_RSM/updater/server/phpUpdate_From_v6.9.0.3.164_to_v7.0.0.3.165/update_post.sql`
- Potential updater validation if the `RS_TOKEN_ALIAS` column definition is not currently applied correctly.
