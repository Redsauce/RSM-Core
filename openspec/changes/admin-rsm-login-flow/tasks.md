## 1. Endpoint Contract Review

- [x] 1.1 Verify `api/v2/staff/get.php` compares `password` directly against `rs_users.RS_PASSWORD` and does not hash plaintext server-side.
- [x] 1.2 Verify `api/v2/staff/get.php` returns only the linked staff item ID and does not return internal user details.

## 2. Staff Lookup

- [x] 2.1 Update `api/v2/staff/get.php` to require `clientID`, `login`, and MD5 `password` before querying or returning a staff item ID.
- [x] 2.2 Remove token and customer-scope requirements from `api/v2/staff/get.php`.
- [x] 2.3 Verify the staff lookup is scoped to the submitted `clientID`.
- [x] 2.4 Preserve the existing success response shape `{ "ID": <staffItemID> }` and existing no-user response behavior.

## 3. Verification

- [x] 3.1 Manually verify `api/v2/staff/get.php` rejects a request with valid credentials but missing `clientID`.
- [x] 3.2 Manually verify `api/v2/staff/get.php` returns the staff item ID for valid `clientID`, `login`, and MD5 `password` without any token.
- [x] 3.3 Manually verify `api/v2/staff/get.php` does not return a staff item ID for wrong-client credentials.
- [x] 3.4 Run PHP linting on `api/v2/staff/get.php`.
