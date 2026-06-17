## ADDED Requirements

### Requirement: Edit token scope and alias
The system SHALL provide a `classLbxTokens_editToken.php` API action that updates only the token fields included in the request, validates authentication against the client inferred from the selected token, and rejects incomplete or invalid customer item scope values.

#### Scenario: Scope and alias are updated
- **WHEN** the request includes a globally unique token, login/password credentials valid for the token's inferred client, a valid `itemTypeID` for that client, a valid `itemID` for that item type ID and client, and an alias
- **THEN** the system SHALL update `rs_tokens.RS_CUSTOMER_ITEM_TYPE_ID`, `rs_tokens.RS_CUSTOMER_ITEM_ID`, and `rs_tokens.RS_TOKEN_ALIAS` for that token and return `result` as `OK`

#### Scenario: Scope is updated without alias
- **WHEN** the request includes a globally unique token, login/password credentials valid for the token's inferred client, a valid `itemTypeID` for that client, and a valid `itemID` for that item type ID and client, but does not include an alias
- **THEN** the system SHALL update `rs_tokens.RS_CUSTOMER_ITEM_TYPE_ID` and `rs_tokens.RS_CUSTOMER_ITEM_ID`, preserve the existing `RS_TOKEN_ALIAS`, and return `result` as `OK`

#### Scenario: Request omits client ID
- **WHEN** the request includes a valid token and valid login/password credentials but no `clientID`
- **THEN** the system SHALL infer the client from the token and process the request for that inferred client

#### Scenario: Credentials do not belong to token client
- **WHEN** the request includes a valid token and login/password credentials that are not valid for the token's inferred client
- **THEN** the system SHALL NOT update any token row and SHALL return `result` as `NOK`

#### Scenario: Only item type ID is supplied
- **WHEN** the request includes a non-empty `itemTypeID` value and no `itemID` value
- **THEN** the system SHALL reject the request without updating `rs_tokens`

#### Scenario: Only item ID is supplied
- **WHEN** the request includes no `itemTypeID` value and a non-empty `itemID` value
- **THEN** the system SHALL reject the request without updating `rs_tokens`

#### Scenario: Item does not exist for client
- **WHEN** the request includes an `itemTypeID` and `itemID` pair that does not identify an existing item for the token's inferred client
- **THEN** the system SHALL reject the request without updating `rs_tokens`

#### Scenario: Alias-only request preserves scope
- **WHEN** the request includes a token, login/password credentials valid for the token's inferred client, no `itemTypeID`, no `itemID`, and an alias
- **THEN** the system SHALL leave `RS_CUSTOMER_ITEM_TYPE_ID` and `RS_CUSTOMER_ITEM_ID` unchanged, update `RS_TOKEN_ALIAS`, and return `result` as `OK`

#### Scenario: No editable values are supplied
- **WHEN** the request includes a token and login/password credentials valid for the token's inferred client, but no `itemTypeID`, no `itemID`, and no alias
- **THEN** the system SHALL leave `rs_tokens` unchanged and return `result` as `OK`
