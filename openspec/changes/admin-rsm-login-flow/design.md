## Context

The RSM administrator login flow uses two API v2 identity endpoints:

- `api/v2/user/get.php` validates credentials against `rs_users` and returns all matching internal `RS_USER_ID` and `RS_CLIENT_ID` pairs.
- `api/v2/staff/get.php` resolves the same credentials to the linked staff item ID stored in `rs_users.RS_ITEM_ID`.

`api/v2/user/get.php` is used before the caller has selected a client, so it must not require `clientID`. `api/v2/staff/get.php` runs after client selection, so it uses `clientID` but still does not require `RStoken`. Both endpoints compare the request password directly with `rs_users.RS_PASSWORD`. The caller is responsible for sending the password as the same MD5 hash format stored in the database. The endpoint must not transform plaintext into MD5 because existing clients already send hashed credentials and double hashing would break login.

## Goals / Non-Goals

**Goals:**
- Document and implement the two-step API v2 login contract for RSM administrator users.
- Allow `api/v2/user/get.php` to validate `login` and MD5 `password` without requiring `RStoken` or `clientID`, returning every matching `userID`/`clientID` pair.
- Allow `api/v2/staff/get.php` to return the linked staff item ID for matching `clientID`, `login`, and MD5 `password` without requiring any token.
- Preserve the existing JSON body contract and response shape for successful lookups.
- Keep credential comparison compatible with MD5 values already stored in `rs_users.RS_PASSWORD`.

**Non-Goals:**
- Introduce a new password hashing scheme or migrate stored passwords away from MD5.
- Accept plaintext passwords in these endpoints.
- Replace the existing API token model.
- Redesign administrator/session management outside these two endpoint calls.
- Add broad authorization changes to unrelated API endpoints.

## Decisions

### Keep MD5 hashing as a caller contract

The API will continue to compare `requestBody->password` directly against `rs_users.RS_PASSWORD`. Callers must send the MD5 hash, not plaintext.

Alternative considered: hash the submitted password inside the endpoint. This was rejected because existing clients already submit MD5 hashes and server-side hashing would change the expected comparison value.

### Split multi-client discovery from client-specific staff lookup

`api/v2/user/get.php` is the first login step and must not fail solely because `RStoken` or `clientID` is absent. It still requires a valid JSON body, and it returns every matching user/client pair for the submitted login and MD5 password.

`api/v2/staff/get.php` is the second step and must require `clientID` before returning staff details. This lets the caller first discover matching clients, select the intended client, and then resolve the staff item ID for that specific client without an API token.

Alternative considered: require token on the staff step. This was rejected because the login flow must resolve the administrator's staff item from credentials and selected client without any API token.

### Do not apply token customer-scope checks to login endpoints

Customer-scoped token logic applies to token-protected API item operations. These login endpoints are credential-based and do not require tokens, so `staff/get.php` must not call token customer-scope helpers before returning the staff item ID.

Alternative considered: keep optional customer-scope checks when a token is supplied. This was rejected for this flow because tokens are not part of the endpoint contract and optional token-dependent behavior would make login responses inconsistent.

## Risks / Trade-offs

- MD5 remains weak for password storage -> This change documents current compatibility behavior only; password migration is out of scope.
- The first step returns internal user IDs and client IDs without token authentication -> The response is limited to successful credential validation by login and MD5 password.
- The second step returns a staff item ID without token authentication -> The response is limited to the matched `clientID`, `login`, and MD5 password combination.

## Migration Plan

1. Update `api/v2/user/get.php` so missing `RStoken` and missing `clientID` do not cause credential discovery to fail.
2. Update `api/v2/staff/get.php` to require `clientID`, `login`, and MD5 `password`, and to stop requiring or applying token checks.
3. Verify existing clients send `login` and MD5 `password` to the discovery step, then `clientID`, `login`, and MD5 `password` to the staff lookup step.
4. Rollback is limited to restoring the prior token/customer-scope behavior in `staff/get.php` if a dependent caller has not been migrated.

## Open Questions

- Should the staff lookup return an empty response or an explicit error when multiple rows match the same `clientID`, `login`, and MD5 `password`?
