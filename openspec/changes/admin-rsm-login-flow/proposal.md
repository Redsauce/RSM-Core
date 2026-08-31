## Why

RSM administrator staff lookup depends on a v2 identity endpoint, but the expected contract is not documented as a capability. This change defines the administrator staff lookup so it can run with credentials and a selected client without API tokens.

## What Changes

- Define the RSM administrator staff lookup through `api/v2/staff/get.php`.
- Treat `api/v2/staff/get.php` as the staff-detail lookup step that accepts `clientID`, `login`, and `password` without requiring `RStoken`.
- Require callers to send `password` as the MD5 hash stored in `rs_users.RS_PASSWORD`; the endpoint does not hash plaintext passwords.
- Preserve client scoping on the staff lookup and avoid leaking staff IDs when credentials do not authorize the request.

## Capabilities

### New Capabilities
- `admin-rsm-login`: Defines the RSM administrator staff lookup through the API v2 staff endpoint.

### Modified Capabilities

## Impact

- API v2 endpoint behavior:
  - `Server/htdocs/AppController/commands_RSM/api/v2/staff/get.php`
- Shared request, token, and client resolution utilities used by those endpoints:
  - `Server/htdocs/AppController/commands_RSM/utilities/RStools.php`
  - `Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php`
  - `Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php`
- Tests or manual API verification for login with hashed passwords, tokenless staff lookup by client, and rejected invalid credentials.
