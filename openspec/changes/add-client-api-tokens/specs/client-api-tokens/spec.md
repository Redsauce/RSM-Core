## ADDED Requirements

### Requirement: API endpoint classification
The system SHALL review and classify every PHP endpoint under `Server/htdocs/AppController/commands_RSM/api/` before the change is considered complete.

#### Scenario: Item-backed endpoint is classified
- **WHEN** an API endpoint can return, mutate, queue, stream, export, or otherwise act on item-backed data
- **THEN** the endpoint MUST enforce customer scope for customer-scoped tokens

#### Scenario: Non-item endpoint is classified
- **WHEN** an API endpoint does not access item-backed data
- **THEN** the implementation MUST document why customer scope is not required for that endpoint

#### Scenario: Unclassified token endpoint
- **WHEN** an API endpoint accepts `RStoken` and has not been classified
- **THEN** the endpoint MUST fail closed for customer-scoped tokens

### Requirement: Token customer scope
The system SHALL support API tokens with an optional customer item type and customer item association stored with the token record.

#### Scenario: Existing token remains standard
- **WHEN** an enabled API token has no customer item type and no customer item association
- **THEN** the system MUST authenticate and authorize the token using the existing token behavior without customer item restrictions

#### Scenario: Customer-scoped token is identified
- **WHEN** an enabled API token has both a positive customer item type association and a positive customer item association
- **THEN** the system MUST treat the token as customer-scoped for API v1 and v2 item operations

#### Scenario: Partial customer scope is invalid
- **WHEN** an enabled API token has only one of customer item type or customer item populated
- **THEN** the system MUST deny customer-scoped API item access for that token

### Requirement: RSM client token configuration
The RSM client token configuration endpoints SHALL support creating and managing tokens with optional customer scope metadata.

#### Scenario: Create standard token from RSM client
- **WHEN** the RSM client creates a token without customer item type ID and customer item ID
- **THEN** the system MUST create a standard disabled token with no customer scope

#### Scenario: Create customer-scoped token from RSM client
- **WHEN** the RSM client creates a token with both customer item type ID and customer item ID
- **THEN** the system MUST store both values on the token record and return the created token data

#### Scenario: List tokens for configuration
- **WHEN** the RSM client lists tokens for a client
- **THEN** the response MUST include each token's customer item type ID and customer item ID in addition to existing token fields

#### Scenario: Enable or disable scoped token
- **WHEN** the RSM client enables or disables a customer-scoped token
- **THEN** the system MUST change only the enabled state and MUST preserve the token customer scope metadata

#### Scenario: Delete scoped token
- **WHEN** the RSM client deletes a customer-scoped token
- **THEN** the system MUST delete the token and its permissions using the existing token ownership rules

### Requirement: Customer dependency definition
For customer-scoped tokens, the system SHALL determine item access through a direct `identifier` property on the target item type that refers to the token customer item type and whose value equals the token customer item ID.

#### Scenario: Item has matching customer dependency
- **WHEN** a customer-scoped token accesses an item whose customer dependency identifier refers to the token customer item type and equals the token customer item ID
- **THEN** the item MUST be considered inside the token customer scope

#### Scenario: Item has no matching customer dependency
- **WHEN** a customer-scoped token accesses an item with no customer dependency identifier, a different customer item type, or a different customer item ID
- **THEN** the item MUST be considered outside the token customer scope

#### Scenario: Customer dependency is ambiguous
- **WHEN** the system cannot determine a single direct customer dependency identifier for the item type
- **THEN** the system MUST deny customer-scoped token access to that item type

### Requirement: Shared customer-scope helpers
The system SHALL centralize customer-scope checks in shared utility functions used by API endpoints.

#### Scenario: Endpoint checks item scope
- **WHEN** an endpoint needs to authorize access to a specific item for a token
- **THEN** the endpoint MUST call a shared customer-scope helper instead of implementing endpoint-local customer-scope SQL

#### Scenario: Endpoint checks batch item scope
- **WHEN** an endpoint needs to authorize access to multiple items for a token
- **THEN** the endpoint MUST call a shared batch customer-scope helper that rejects the batch if any item is outside scope

#### Scenario: Endpoint filters item queries
- **WHEN** an endpoint lists, counts, searches, or feeds items for a customer-scoped token
- **THEN** the endpoint MUST use a shared filter helper or shared query path that applies the customer dependency constraint consistently

### Requirement: Scoped item-backed reads
API v1 and v2 item read, list, count, search, properties, audit trail, pending actions, feeds, file, and picture endpoints SHALL restrict customer-scoped token responses to data from items inside the token customer scope.

#### Scenario: List endpoint filters results
- **WHEN** a customer-scoped token requests multiple items and the query matches both in-scope and out-of-scope items
- **THEN** the response MUST include only the in-scope items

#### Scenario: Direct read outside scope
- **WHEN** a customer-scoped token requests a specific item outside the token customer scope
- **THEN** the endpoint MUST NOT return that item's data

#### Scenario: Properties read outside scope
- **WHEN** a customer-scoped token requests properties or property values for an item outside the token customer scope
- **THEN** the endpoint MUST NOT return that item's property data

#### Scenario: Audit trail read outside scope
- **WHEN** a customer-scoped token requests audit trail for an item outside the token customer scope
- **THEN** the endpoint MUST NOT return that item's audit trail data

#### Scenario: File or picture read outside scope
- **WHEN** a customer-scoped token requests a file or picture property that belongs to an item outside the token customer scope
- **THEN** the endpoint MUST NOT return the file or picture data

#### Scenario: Standard token read is unchanged
- **WHEN** a standard API token requests item data
- **THEN** customer-scope filtering MUST NOT be applied

### Requirement: Scoped item creation
API v1 and v2 item create endpoints SHALL require customer-scoped token payloads to create only items inside the token customer scope.

#### Scenario: Create with matching dependency
- **WHEN** a customer-scoped token creates an item with the required customer dependency identifier referring to the token customer item type and set to the token customer item ID
- **THEN** the item creation MAY proceed to existing CREATE permission validation

#### Scenario: Create without matching dependency
- **WHEN** a customer-scoped token creates an item without the required customer dependency identifier or with a different customer item ID
- **THEN** the endpoint MUST reject the creation

### Requirement: Scoped item mutation
API v1 and v2 item update and delete endpoints SHALL allow customer-scoped tokens to mutate only items inside the token customer scope, including mutations of file and image properties.

#### Scenario: Update outside scope
- **WHEN** a customer-scoped token attempts to update an item outside the token customer scope
- **THEN** the endpoint MUST reject the update before changing any property values

#### Scenario: File or picture update outside scope
- **WHEN** a customer-scoped token attempts to update a file or picture property on an item outside the token customer scope
- **THEN** the endpoint MUST reject the update before changing the property value or stored binary data

#### Scenario: Delete outside scope
- **WHEN** a customer-scoped token attempts to delete an item outside the token customer scope
- **THEN** the endpoint MUST reject the delete before removing any item data

#### Scenario: Batch mutation contains out-of-scope item
- **WHEN** a customer-scoped token submits a batch update or delete containing at least one out-of-scope item
- **THEN** the endpoint MUST reject the batch without partially mutating in-scope items

### Requirement: Existing token permissions remain required
Customer scope SHALL be enforced in addition to existing token property permissions and visibility checks.

#### Scenario: In-scope item lacks property permission
- **WHEN** a customer-scoped token accesses an in-scope item but lacks the required property permission for the requested operation
- **THEN** the endpoint MUST deny access according to the existing permission behavior

#### Scenario: Out-of-scope item has property permission
- **WHEN** a customer-scoped token has the required property permission for an out-of-scope item
- **THEN** the endpoint MUST deny access because customer scope is not satisfied

### Requirement: Scoped identity lookup
API v1 and v2 staff and user lookup endpoints SHALL NOT expose staff item IDs or internal user IDs to customer-scoped tokens unless the linked staff item is explicitly inside the token customer scope.

#### Scenario: Staff lookup outside scope
- **WHEN** a customer-scoped token requests a staff ID and the matching staff item is not inside the token customer scope
- **THEN** the endpoint MUST NOT return the staff item ID

#### Scenario: User lookup outside scope
- **WHEN** a customer-scoped token requests a user ID and the matching user's staff item is not inside the token customer scope
- **THEN** the endpoint MUST NOT return the user ID

#### Scenario: User ID is internal
- **WHEN** the system evaluates a customer-scoped token request to a user lookup endpoint
- **THEN** the system MUST treat `RS_USER_ID` as an internal `rs_users` identifier and MUST use the linked `rs_users.RS_ITEM_ID` staff item, not `RS_USER_ID`, for customer-scope validation

#### Scenario: Staff item cannot be scoped
- **WHEN** a customer-scoped token requests staff or user lookup and staff items do not have a direct customer dependency identifier
- **THEN** the endpoint MUST deny the lookup for that customer-scoped token
