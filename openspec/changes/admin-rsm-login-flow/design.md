## Context

The RSM administrator login flow uses the API v2 staff identity endpoint:

- `api/v2/staff/get.php` validates credentials for a submitted client and resolves them to the linked staff item ID stored in `rs_users.RS_ITEM_ID`.

`api/v2/staff/get.php` runs after client selection, so it uses `clientID` but still does not require `RStoken`. The endpoint compares the request password directly with `rs_users.RS_PASSWORD`. The caller is responsible for sending the password as the same MD5 hash format stored in the database. The endpoint must not transform plaintext into MD5 because existing clients already send hashed credentials and double hashing would break login.

## Goals / Non-Goals

**Goals:**
- Document and implement the API v2 staff lookup contract for RSM administrator users.
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

### Keep staff lookup client-specific

`api/v2/staff/get.php` must require `clientID` before returning staff details. This lets the caller resolve the staff item ID for a specific client without an API token.

Alternative considered: require token on the staff step. This was rejected because the login flow must resolve the administrator's staff item from credentials and selected client without any API token.

### Do not apply token customer-scope checks to the login endpoint

Customer-scoped token logic applies to token-protected API item operations. The staff login endpoint is credential-based and does not require tokens, so `staff/get.php` must not call token customer-scope helpers before returning the staff item ID.

Alternative considered: keep optional customer-scope checks when a token is supplied. This was rejected for this flow because tokens are not part of the endpoint contract and optional token-dependent behavior would make login responses inconsistent.

## Risks / Trade-offs

- MD5 remains weak for password storage -> This change documents current compatibility behavior only; password migration is out of scope.
- The endpoint returns a staff item ID without token authentication -> The response is limited to the matched `clientID`, `login`, and MD5 password combination.

## Migration Plan

1. Update `api/v2/staff/get.php` to require `clientID`, `login`, and MD5 `password`, and to stop requiring or applying token checks.
2. Verify existing clients send `clientID`, `login`, and MD5 `password` to the staff lookup step.
3. Rollback is limited to restoring the prior token/customer-scope behavior in `staff/get.php` if a dependent caller has not been migrated.

## Open Questions

- Should the staff lookup return an empty response or an explicit error when multiple rows match the same `clientID`, `login`, and MD5 `password`?
