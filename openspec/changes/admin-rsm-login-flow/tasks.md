## 1. Endpoint Contract Review

- [x] 1.1 Verify `api/v2/user/get.php` accepts requests with `login` and MD5 `password` when both `RStoken` and `clientID` are absent.
- [x] 1.2 Verify `api/v2/user/get.php` does not return staff item details during the tokenless first login step.
- [x] 1.3 Verify both endpoints compare `password` directly against `rs_users.RS_PASSWORD` and do not hash plaintext server-side.
- [x] 1.4 Verify `api/v2/user/get.php` queries all matching `rs_users` rows by `RS_LOGIN` and `RS_PASSWORD`, returning each row's `RS_USER_ID` and `RS_CLIENT_ID`.

## 2. Staff Lookup

- [x] 2.1 Update `api/v2/staff/get.php` to require `clientID`, `login`, and MD5 `password` before querying or returning a staff item ID.
- [x] 2.2 Remove token and customer-scope requirements from `api/v2/staff/get.php`.
- [x] 2.3 Verify the staff lookup is scoped to the submitted `clientID`.
- [x] 2.4 Preserve the existing success response shape `{ "ID": <staffItemID> }` and existing no-user response behavior.

## 3. Verification

- [ ] 3.1 Manually verify `api/v2/user/get.php` returns every matching `userID`/`clientID` pair for valid `login` and MD5 `password` without `RStoken` or `clientID`.
- [ ] 3.2 Manually verify `api/v2/user/get.php` rejects invalid credentials and plaintext password values that do not match the stored MD5 hash.
- [x] 3.3 Manually verify `api/v2/staff/get.php` rejects a request with valid credentials but missing `clientID`.
- [x] 3.4 Manually verify `api/v2/staff/get.php` returns the staff item ID for valid `clientID`, `login`, and MD5 `password` without any token.
- [x] 3.5 Manually verify `api/v2/staff/get.php` does not return a staff item ID for wrong-client credentials.
- [x] 3.6 Run PHP linting on `api/v2/user/get.php` and `api/v2/staff/get.php`.
