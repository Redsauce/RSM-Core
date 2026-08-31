## 1. Schema and Token Metadata

- [x] 1.1 Add `RS_CUSTOMER_ITEM_TYPE_ID`, `RS_CUSTOMER_ITEM_ID`, and `RS_TOKEN_ALIAS` as nullable columns to `rs_tokens` in `Database/schema.sql`.
- [x] 1.2 Add the guarded `ALTER TABLE rs_tokens` migration to the v6.9.0.3.164 -> v7.0.0.3.165 updater flow used by `Server/htdocs/AppController/commands_RSM/updater/server/phpUpdate_From_v6.9.0.3.164_to_v7.0.0.3.165.php`.
- [x] 1.3 Use `ADD IF NOT EXISTS` or an equivalent per-column existence check for both token scope columns so rerunning the updater does not fail if either column already exists.
- [x] 1.4 Update token creation utilities so new tokens can optionally store a customer item type ID and customer item ID, and default to standard-token behavior when both are omitted.
- [x] 1.5 Update `api/classLbxTokens_newToken.php` to accept the customer item type ID and customer item ID sent by the RSM client and pass them to token creation.
- [x] 1.6 Update `api/classLbxTokens_getTokens.php` and token listing utilities to return the customer item type ID and customer item ID for each token.
- [x] 1.6a Update token listing utilities to return `RS_TOKEN_ALIAS` as `tokenAlias`.
- [x] 1.7 Verify `api/classLbxTokens_enableToken.php` and `api/classLbxTokens_disableToken.php` preserve customer scope metadata while changing only `RS_ENABLED`.
- [x] 1.8 Verify `api/classLbxTokens_deleteToken.php` deletes customer-scoped tokens and their permissions using the existing client ownership checks.

## 2. Customer Scope Utilities

- [x] 2.1 Add token helpers to detect whether a token is customer-scoped and retrieve its customer item type ID and customer item ID.
- [x] 2.2 Add token validation that rejects partial customer scope data when only one of customer item type ID or customer item ID is populated.
- [x] 2.3 Add item helper logic to find the single direct customer `identifier` or `identifiers` dependency property for an item type using the token customer item type ID.
- [x] 2.4 Add an authorization helper that verifies whether a given item ID is inside a customer-scoped token's scope.
- [x] 2.5 Add a filter helper that appends the customer dependency constraint to list/count/search queries before item retrieval.
- [x] 2.6 Add a batch authorization helper that verifies all requested item IDs are inside a customer-scoped token's scope before mutation.
- [x] 2.7 Add a create-payload helper that validates required customer dependency values before creating items with a customer-scoped token.
- [x] 2.8 Create an API endpoint inventory covering every PHP file under `Server/htdocs/AppController/commands_RSM/api/`, with each endpoint classified as item-backed, token-management, public/non-token, or shared/helper.
- [x] 2.9 Add fail-closed handling for customer-scoped tokens on any endpoint that accepts `RStoken` and cannot be confidently classified.
- [x] 2.10 Support customer scope through `identifiers` multi-identifier properties by matching the token customer item ID inside the comma-separated value list.

## 2a. Token Scope And Alias Editing

- [x] 2a.1 Verify `update_post.sql` creates `RS_CUSTOMER_ITEM_TYPE_ID`, `RS_CUSTOMER_ITEM_ID`, and `RS_TOKEN_ALIAS` correctly on `rs_tokens`.
- [x] 2a.2 Add `RSeditToken($RStoken, $clientID, $customerItemTypeID, $customerItemID, $tokenAlias = null)` to `RSMtokensManagement.php`.
- [x] 2a.3 Ensure `RSeditToken` updates only rows matching both `RS_TOKEN` and `RS_CLIENT_ID`, updates scope only when both scope values are supplied, persists the alias only when supplied, and preserves omitted fields.
- [x] 2a.4 Add `Server/htdocs/AppController/commands_RSM/api/classLbxTokens_editToken.php` following the structure of sibling token-management handlers.
- [x] 2a.5 Infer the target `clientID` from the selected token; do not require callers to provide `clientID`.
- [x] 2a.6 Validate required token and authentication inputs through the existing bootstrap/security flow and reject partial customer scope values.
- [x] 2a.7 Verify supplied `itemTypeID` and `itemID` identify an existing item for the inferred token client before updating scope.
- [x] 2a.8 If `itemTypeID`, `itemID`, and `tokenAlias` are all omitted, return `OK` without updating `rs_tokens`.
- [x] 2a.9 Return `result` as `OK` or `NOK` using `RSReturnArrayResults`, consistent with sibling token endpoints.

## 3. API v2 Item Endpoints

- [x] 3.1 Enforce customer scope in `api/v2/items/get.php` for direct ID reads and filtered list reads.
- [x] 3.2 Enforce customer scope in `api/v2/items/getCount.php`.
- [x] 3.3 Enforce customer scope in `api/v2/items/getItemFromProperty.php`.
- [x] 3.4 Reject `api/v2/items/create.php` payloads that do not set the required customer dependency to the token customer item type ID and customer item ID.
- [x] 3.5 Reject `api/v2/items/update.php` requests when any target item is outside scope before applying changes.
- [x] 3.6 Reject `api/v2/items/delete.php` requests when any target item is outside scope before deleting data.
- [x] 3.7 Enforce customer scope in `api/v2/file/get.php` and `api/v2/picture/get.php` before returning binary property data.
- [x] 3.8 Enforce customer scope in `api/v2/properties/get.php` before returning item property metadata or values.
- [x] 3.9 Enforce customer scope in `api/v2/audit/get.php` before returning audit trail data.
- [x] 3.10 Enforce fail-closed customer scope behavior in `api/v2/staff/get.php` and `api/v2/user/get.php`, validating user lookup through the linked `rs_users.RS_ITEM_ID` staff item rather than `RS_USER_ID`.

## 4. API v1 Item and Property Endpoints

- [x] 4.1 Enforce customer scope in `api_getItems.php`, `api_getItemsCount.php`, and `api_getItemFromProperty.php`.
- [x] 4.2 Enforce customer scope in direct item reads through `api_getItem.php`.
- [x] 4.3 Reject creates through `api_createItem.php` that do not set the required customer dependency to the token customer item type ID and customer item ID.
- [x] 4.4 Reject updates through `api_updateItem.php` and `api_updateItems.php` when any target item is outside scope before applying changes.
- [x] 4.5 Reject deletes through `api_deleteItem.php` and `api_deleteItems.php` when any target item is outside scope before deleting data.
- [x] 4.6 Enforce customer scope in `api_getFile.php` and `api_getPicture.php` before returning binary property data.
- [x] 4.7 Enforce customer scope in `api_getProperties.php` before returning item property metadata or values.
- [x] 4.8 Enforce customer scope in `api_getAuditTrail.php` before returning audit trail data.
- [x] 4.9 Enforce fail-closed customer scope behavior in `api_getStaffID.php` and `api_getUserID.php`, validating user lookup through the linked `rs_users.RS_ITEM_ID` staff item rather than `RS_USER_ID`.
- [x] 4.10 Classify and enforce or explicitly exempt `api.php`, `rss.php`, `api_getPendingActions.php`, and `wndAPI_*` endpoints.
- [x] 4.11 Include `api_headers.php` and `public/timezones.php` in the endpoint inventory as no-code-change exemptions unless implementation discovers they directly expose customer-scoped item data.

## 5. Verification

- [x] 5.1 Verify standard tokens still authenticate and authorize existing API v1 item operations without customer-scope filtering.
- [x] 5.2 Verify standard tokens still authenticate and authorize existing API v2 item operations without customer-scope filtering.
- [x] 5.3 Verify customer-scoped tokens can read/list/count only items with the matching customer dependency.
- [ ] 5.4 Verify customer-scoped tokens cannot create, update, or delete items outside their customer scope.
- [x] 5.5 Verify customer-scoped tokens cannot read file or picture data from items outside their customer scope.
- [x] 5.6 Verify customer-scoped tokens cannot read properties or audit trail from items outside their customer scope.
- [ ] 5.7 Verify customer-scoped tokens cannot retrieve staff/user IDs unless the matched staff item is inside scope.
- [x] 5.8 Verify property permissions are still required when customer scope passes.
- [x] 5.9 Verify every API PHP endpoint appears in the endpoint inventory with an enforcement or exemption decision.
- [x] 5.10 Run the available PHP linting or endpoint-level regression checks for all touched API and utility files.
- [ ] 5.11 Verify a valid edit-token request without `clientID` updates the expected scope fields for the selected token after inferring the client from that token.
- [x] 5.12 Verify a valid edit-token request that omits `tokenAlias` preserves the existing `RS_TOKEN_ALIAS`.
- [x] 5.13 Verify a valid edit-token request that omits both `itemTypeID` and `itemID` but includes `tokenAlias` preserves both scope fields and updates the alias.
- [x] 5.14 Verify a valid edit-token request that omits `itemTypeID`, `itemID`, and `tokenAlias` leaves `rs_tokens` unchanged.
- [x] 5.15 Verify edit-token requests with invalid login/password for the token's client, partial scope values, or a missing item do not update `rs_tokens`.
