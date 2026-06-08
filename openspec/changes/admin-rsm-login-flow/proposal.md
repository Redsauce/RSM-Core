## Why

RSM administrator login currently depends on two v2 identity endpoints, but the expected contract is not documented as a capability. This change defines the administrator login flow so credential discovery and staff item lookup can run without API tokens.

## What Changes

- Define the RSM administrator login flow as a two-step API v2 process.
- Treat `api/v2/user/get.php` as the credential-discovery step that accepts `login` and `password` without requiring `RStoken` or `clientID`, and returns every matching `userID`/`clientID` pair.
- Treat `api/v2/staff/get.php` as the staff-detail lookup step that accepts `clientID`, `login`, and `password` without requiring `RStoken`.
- Require callers to send `password` as the MD5 hash stored in `rs_users.RS_PASSWORD`; the endpoint does not hash plaintext passwords.
- Preserve client scoping on the staff lookup and avoid leaking staff IDs when credentials do not authorize the request.

## Capabilities

### New Capabilities
- `admin-rsm-login`: Defines the two-step RSM administrator login flow through API v2 user and staff endpoints.

### Modified Capabilities

## Impact

- API v2 endpoint behavior:
  - `Server/htdocs/AppController/commands_RSM/api/v2/user/get.php`
  - `Server/htdocs/AppController/commands_RSM/api/v2/staff/get.php`
- Shared request, token, and client resolution utilities used by those endpoints:
  - `Server/htdocs/AppController/commands_RSM/utilities/RStools.php`
  - `Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php`
  - `Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php`
- Tests or manual API verification for login with hashed passwords, tokenless user discovery, tokenless staff lookup by client, and rejected invalid credentials.
