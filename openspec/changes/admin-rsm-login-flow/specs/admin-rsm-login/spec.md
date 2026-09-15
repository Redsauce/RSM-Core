## ADDED Requirements

### Requirement: Administrator staff login lookup
The system SHALL authenticate an RSM administrator staff lookup through the API v2 staff endpoint.

#### Scenario: Staff lookup resolves staff without token
- **WHEN** a caller sends `clientID`, `login`, and `password` to `api/v2/staff/get.php` without `RStoken`
- **THEN** the endpoint MUST validate the credentials for that client without rejecting the request solely because `RStoken` is absent

#### Scenario: Staff lookup returns staff item for client
- **WHEN** a caller sends `clientID`, `login`, and `password` to `api/v2/staff/get.php` and the credentials match a user in that client
- **THEN** the endpoint MUST return the linked staff item ID from `rs_users.RS_ITEM_ID`

### Requirement: Hashed password credential contract
The system SHALL treat the request `password` field as the MD5 hash value stored in `rs_users.RS_PASSWORD`.

#### Scenario: Password matches stored MD5 hash
- **WHEN** the request `password` value exactly matches `rs_users.RS_PASSWORD` for the submitted `clientID` and `login`
- **THEN** the credential check MUST be considered valid

#### Scenario: Plaintext password is submitted
- **WHEN** the request `password` value is plaintext and does not exactly match `rs_users.RS_PASSWORD`
- **THEN** the credential check MUST fail and the endpoint MUST NOT hash the plaintext value server-side

#### Scenario: Endpoint receives pre-hashed value
- **WHEN** the request `password` value is already the MD5 hash expected by the database
- **THEN** the endpoint MUST compare that value directly without applying an additional hash


### Requirement: Login response data boundaries
The system SHALL return only the identity value owned by the staff lookup endpoint.

#### Scenario: Staff lookup succeeds
- **WHEN** `api/v2/staff/get.php` validates the submitted `clientID`, `login`, and MD5 `password`
- **THEN** the endpoint MUST return a JSON response containing only the linked staff item `ID`

#### Scenario: Credentials fail
- **WHEN** the staff lookup endpoint cannot find a user matching the submitted `clientID`, `login`, and MD5 `password`
- **THEN** the endpoint MUST NOT return any internal user ID or staff item ID

### Requirement: Client-specific staff lookup
The system SHALL use `clientID`, `login`, and MD5 `password` to resolve the staff item ID from the second login step without requiring tokens.

#### Scenario: Client ID is missing
- **WHEN** `api/v2/staff/get.php` is called without `clientID`
- **THEN** the endpoint MUST reject the request using the existing missing-field error behavior

#### Scenario: Credentials match another client
- **WHEN** the submitted `login` and MD5 `password` match a user in a different `RS_CLIENT_ID` than the submitted `clientID`
- **THEN** `api/v2/staff/get.php` MUST NOT return that user's staff item ID

#### Scenario: Token is supplied
- **WHEN** `api/v2/staff/get.php` receives `RStoken` in addition to valid `clientID`, `login`, and MD5 `password`
- **THEN** the endpoint MUST NOT require or apply token validation before returning the staff item ID
