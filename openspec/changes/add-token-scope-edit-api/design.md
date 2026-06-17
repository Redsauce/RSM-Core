## Context

Token management endpoints in `Server/htdocs/AppController/commands_RSM/api` are small PHP handlers that require `../utilities/RSdatabase.php`, read request values from `$GLOBALS[$cstRS_POST]`, delegate database writes to token-management helpers, and return XML-style RSM arrays through `RSReturnArrayResults`.

`RSdatabase.php` already infers `$GLOBALS[$cstRS_POST][$cstClientID]` from `$GLOBALS[$cstRS_POST][$cstRStoken]` when `clientID` is not supplied, and `RSsecurityCheck.php` validates user access after that inference. The edit endpoint should rely on this flow: the selected token is globally unique, determines the client, and the supplied login/password must authenticate against that token's client.

The `rs_tokens` table has been extended with optional customer scope fields, `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID`, plus `RS_TOKEN_ALIAS`. Existing runtime helpers already treat partially configured customer scope as invalid, so the edit endpoint must preserve the invariant that scope is either completely empty or contains both a valid item type ID and item ID.

## Goals / Non-Goals

**Goals:**
- Add `classLbxTokens_editToken.php` with the same bootstrap, authentication context, and response style as existing `classLbxTokens_disableToken.php` and `classLbxTokens_enableToken.php`.
- Infer the target `clientID` from the selected token instead of requiring `clientID` in the request.
- Update the target token row only when supplied login/password credentials are valid for the inferred token client.
- Store the alias in `rs_tokens.RS_TOKEN_ALIAS` only when the request includes an alias value.
- Store the provided `itemTypeID` and `itemID` in `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` when both are included.
- Leave `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` unchanged when scope inputs are omitted.
- Perform no update when `itemTypeID`, `itemID`, and `tokenAlias` are all omitted.
- Reject partially supplied scope inputs before writing invalid token state.

**Non-Goals:**
- Do not change token permission assignment behavior.
- Do not introduce a new v2 JSON route.
- Do not change how enabled/disabled state is represented.
- Do not migrate existing tokens beyond ensuring the required columns exist.

## Decisions

- Implement the endpoint as a classic `classLbxTokens_*` handler.
  - Rationale: the requested file and neighboring handlers use this convention, so the UI/backend integration can call it consistently.
  - Alternative considered: add a v2 JSON endpoint. That would not match the requested file name or existing token-management UI pattern.

- Add a token helper such as `RSeditToken($RStoken, $clientID, $customerItemTypeID, $customerItemID, $alias = null)` in `RSMtokensManagement.php`.
  - Rationale: existing handlers delegate writes to helpers (`RSenableToken`, `RSdisableToken`, `RScreateToken`), keeping SQL out of the API file.
  - Alternative considered: inline SQL in `classLbxTokens_editToken.php`. That would be inconsistent and harder to reuse or test.

- Do not require `clientID` from the caller.
  - Rationale: tokens are unique across clients, and existing bootstrap code can infer the client from the token before running user authentication checks.
  - Alternative considered: require both token and `clientID`. That adds redundant input and creates a mismatch risk that the token itself can avoid.

- Require `itemTypeID` to be a client item type ID.
  - Rationale: the request supplies an item type ID, not an item type name, so the endpoint can validate and persist the numeric ID directly.
  - Alternative considered: accept item type names and resolve them through `parseITID`. That would add behavior the caller does not need.

- Validate complete scope before update.
  - Rationale: `RSisTokenCustomerScopeValid()` fails closed when only one of the two scope fields is set, so edit must never persist partial scope.
  - Alternative considered: allow `NULL` for one field and rely on consumers to reject later. That would create broken tokens and confusing authorization failures.

- Do not update fields omitted from the request.
  - Rationale: the edit endpoint should only mutate fields for which the caller sent new content. An alias-only request must not clear scope.
  - Alternative considered: require all fields on every request. That would make alias-only corrections impossible.

- Preserve the existing alias when the request omits `tokenAlias`.
  - Rationale: alias is optional and callers that only edit scope should not accidentally erase existing labels.
  - Alternative considered: always overwrite alias with an empty string when omitted. That would make omission destructive.

## Risks / Trade-offs

- Invalid credentials could allow editing a token without client authorization -> mitigate by relying on the existing `RSdatabase.php` and `RSsecurityCheck.php` flow after client inference from the token.
- Invalid item ownership could scope a token to another customer's item -> mitigate by parsing the item type for the inferred token client and checking `verifyItemExists($itemID, $itemTypeID, $clientID)` before update.
- SQL injection risk from token or alias values -> mitigate by following the repository's existing escaping/query helper conventions where available and normalizing integer inputs with `intval`.
- Existing token list may not display aliases -> include a follow-up task to expose `RS_TOKEN_ALIAS` from `RStokensFromClient` if the UI needs to show edited aliases.
- The updater currently shows `RS_TOKEN_ALIAS` as a separate `ADD IF NOT EXISTS` after a semicolon -> verify and correct the SQL so the alias column is actually created in the same migration.
