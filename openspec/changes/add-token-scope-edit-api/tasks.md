## 1. Data And Helpers

- [x] 1.1 Verify `update_post.sql` creates `RS_CUSTOMER_ITEM_TYPE_ID`, `RS_CUSTOMER_ITEM_ID`, and `RS_TOKEN_ALIAS` correctly on `rs_tokens`; fix the `RS_TOKEN_ALIAS` `ALTER TABLE` statement if needed.
- [x] 1.2 Extend `RStokensFromClient($clientID)` to return `RS_TOKEN_ALIAS` as `tokenAlias` so edited aliases are visible to token-management callers.
- [x] 1.3 Add `RSeditToken($RStoken, $clientID, $customerItemTypeID, $customerItemID, $tokenAlias = null)` to `RSMtokensManagement.php`.
- [x] 1.4 Ensure `RSeditToken` updates only rows matching both `RS_TOKEN` and `RS_CLIENT_ID`, updates scope only when both scope values are supplied, persists the alias to `RS_TOKEN_ALIAS` only when supplied, and preserves omitted fields.
- [x] 1.5 Use the `clientID` inferred from the selected token; do not require callers to provide `clientID`.

## 2. API Endpoint

- [x] 2.1 Add `Server/htdocs/AppController/commands_RSM/api/classLbxTokens_editToken.php` following the structure of `classLbxTokens_disableToken.php` and `classLbxTokens_enableToken.php`.
- [x] 2.2 Read `token`, login/password authentication data, `itemTypeID`, `itemID`, and optional `tokenAlias` from `$GLOBALS[$cstRS_POST]`, with aliases or defaults only where existing API conventions require them.
- [x] 2.3 Validate required token and authentication inputs, rely on existing bootstrap/security checks to authenticate login/password against the token-inferred client, and reject requests that supply only one customer scope value.
- [x] 2.4 Validate that supplied `itemTypeID` values are numeric client item type IDs.
- [x] 2.5 When both scope values are supplied, verify the target item exists for the supplied item type ID and current client before calling `RSeditToken`.
- [x] 2.6 If `itemTypeID`, `itemID`, and `tokenAlias` are all omitted, return `OK` without updating `rs_tokens`.
- [x] 2.7 Return `result` as `OK` or `NOK` using `RSReturnArrayResults`, consistent with sibling token endpoints.

## 3. Verification

- [x] 3.1 Run PHP syntax checks on the new API file and modified utility file.
- [ ] 3.2 Verify a valid request without `clientID` updates the expected scope fields for the selected token after inferring the client from that token.
- [ ] 3.3 Verify a valid request that omits `tokenAlias` preserves the existing `RS_TOKEN_ALIAS`.
- [ ] 3.4 Verify a valid request that omits both `itemTypeID` and `itemID` but includes `tokenAlias` preserves both scope fields and updates the alias.
- [ ] 3.5 Verify a valid request that omits `itemTypeID`, `itemID`, and `tokenAlias` leaves `rs_tokens` unchanged.
- [ ] 3.6 Verify requests with invalid login/password for the token's client, partial scope values, or a missing item do not update `rs_tokens`.
- [ ] 3.7 Use `Server/htdocs/AppController/commands_RSM/itemsManager/classLbxPntItemTypes_getItems.php` as the concrete `getItems` endpoint when validating item lookup behavior related to this change.
