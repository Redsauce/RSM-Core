## ADDED Requirements

### Requirement: Token roles and management contract
The system SHALL support standalone tokens, master token templates, and child tokens. Token creation SHALL accept optional `isMasterTemplate` and `parentMasterTokenID` fields, and token listing operations SHALL return both fields. Omitting both fields SHALL create a standalone token. A token's role and parent relationship MUST be immutable after creation.

#### Scenario: Existing caller creates a token
- **WHEN** a caller creates a token without `isMasterTemplate` or `parentMasterTokenID`
- **THEN** the system creates a standalone token with `RS_MASTER_TEMPLATE` false and `RS_PARENT_MASTER_TOKEN` equal to `0`

#### Scenario: Create a master template
- **WHEN** an authorized administrator creates a token with `isMasterTemplate` equal to true and no positive parent ID
- **THEN** the system stores the token as a master template and returns its master status in token listings

#### Scenario: Create a child token
- **WHEN** an authorized administrator creates a token with `parentMasterTokenID` referencing a valid master in the same client
- **THEN** the system stores the token as a non-master child and returns the parent ID in token listings

#### Scenario: Edit attempts to change a role
- **WHEN** an edit request supplies `isMasterTemplate` or `parentMasterTokenID` for an existing token
- **THEN** the system rejects the role or parent change and leaves the token relationship unchanged

### Requirement: Valid direct master relationships
The system MUST only allow a child to reference an existing master template belonging to the same client. A master template MUST have parent ID `0`, inheritance MUST be limited to one level, and invalid relationship metadata MUST fail closed.

#### Scenario: Parent belongs to another client
- **WHEN** a create request references a master token ID owned by another client
- **THEN** the system rejects the request without changing the token

#### Scenario: Parent is not a master
- **WHEN** a create request references a standalone or child token as the parent
- **THEN** the system rejects the request without changing the token

#### Scenario: Master is assigned a parent
- **WHEN** a create request would make a token both a master template and a child
- **THEN** the system rejects the request without changing the token

#### Scenario: Stored relationship becomes invalid
- **WHEN** a token has a positive parent ID that does not resolve to a master in the same client
- **THEN** the system denies authentication and permission resolution for that token

### Requirement: Master templates cannot authenticate
The system MUST reject every API request that presents a master template as its authentication token, regardless of the master's enabled state or configured permissions. Administrative token-management operations MAY address a master only after their normal administrator authentication succeeds.

#### Scenario: Enabled master presented to API v1
- **WHEN** an API v1 request presents a token whose `RS_MASTER_TEMPLATE` value is true
- **THEN** the system terminates authentication with the endpoint's unauthorized or forbidden response before accessing client data

#### Scenario: Master presented to API v2 or non-property endpoint
- **WHEN** an API v2, trigger, feed, file, picture, or other token-authenticated request presents a master template
- **THEN** the system rejects the request before executing the requested operation

#### Scenario: Administrator manages a master
- **WHEN** an authenticated administrator selects a master template in a token-management call
- **THEN** the system permits supported operations such as enable, disable, list, delete when unreferenced, and permission administration without permitting role conversion

### Requirement: Child tokens inherit property permissions exclusively
For an enabled child token with a valid parent relationship to an enabled master, the system SHALL evaluate CREATE, READ, WRITE, and DELETE permission checks using only the `rs_token_permissions` rows owned by the parent master token ID. The system MUST NOT combine parent and child permission rows. Both the child and its parent master MUST be enabled for the child to authenticate.

#### Scenario: Parent grants a permission
- **WHEN** an enabled child authenticates, its master is enabled, and the master grants the requested operation for the property
- **THEN** the permission check succeeds subject to all other child-specific authorization constraints

#### Scenario: Parent does not grant a permission
- **WHEN** an enabled child authenticates and its master lacks the requested operation for the property
- **THEN** the permission check fails even if an unexpected permission row exists for the child

#### Scenario: Master permission changes
- **WHEN** an administrator adds or removes a permission on a master template
- **THEN** all of its children use the updated permission set on their next authorization check without copying rows

#### Scenario: Child retains its own scope
- **WHEN** a child inherits permissions from a master and has customer-scope metadata
- **THEN** the system applies the child's customer scope, enabled state, and other child-specific restrictions in addition to inherited permissions

#### Scenario: Master is disabled or unpublished
- **WHEN** an administrator disables or unpublishes a master template
- **THEN** all child tokens referencing that master immediately fail authentication even if the child tokens remain enabled

#### Scenario: Master is enabled again
- **WHEN** an administrator enables a previously disabled master template
- **THEN** its enabled children with otherwise valid metadata can authenticate again using the master's current permissions

#### Scenario: One child is disabled
- **WHEN** an administrator disables a child token whose master remains enabled
- **THEN** that child immediately fails authentication while its master and enabled sibling tokens remain unchanged and operational

#### Scenario: Disabled child remains blocked after master publication
- **WHEN** a master is enabled while one of its child tokens remains disabled
- **THEN** the disabled child continues to fail authentication while other enabled and otherwise valid children can authenticate

### Requirement: Children do not own permission rows
The system MUST reject permission mutations targeted at child tokens and SHALL remove any child-owned `rs_token_permissions` rows when a token becomes a child. Permission reads for a selected child SHALL return its master's effective permissions and indicate that they are inherited.

#### Scenario: Newly created child has no permissions
- **WHEN** an authorized administrator creates a child with a valid master parent
- **THEN** the system creates no `rs_token_permissions` rows for that child

#### Scenario: Permission write targets a child
- **WHEN** a permission create or delete operation selects a child token
- **THEN** the system rejects the mutation and leaves both child and master permissions unchanged

#### Scenario: Administrator views child permissions
- **WHEN** an administrator requests permissions for a child token
- **THEN** the system returns the effective master permissions and identifies the response as inherited from the parent master token ID

### Requirement: Immutable roles and safe deletion
The system MUST NOT support promotion, demotion, parent assignment, parent removal, or parent reassignment for existing tokens. The system MUST reject deletion of a master while any child references it.

#### Scenario: Existing standalone token cannot become a master or child
- **WHEN** an administrator attempts to assign master status or a parent to an existing standalone token
- **THEN** the system rejects the request and preserves the standalone token

#### Scenario: Existing child cannot change parent
- **WHEN** an administrator attempts to clear or replace an existing child's parent
- **THEN** the system rejects the request and preserves the original parent relationship

#### Scenario: Delete referenced master
- **WHEN** an authorized administrator attempts to delete a master that has one or more children
- **THEN** the system rejects deletion and does not detach or delete any child

#### Scenario: Delete a child
- **WHEN** an authorized administrator deletes a child token
- **THEN** the system deletes the child and defensively removes any permission rows owned by that child without changing the master

### Requirement: Backward-compatible storage migration
The database schema and updater SHALL define `RS_MASTER_TEMPLATE` as false by default and `RS_PARENT_MASTER_TOKEN` as `0` by default, and SHALL index child lookups by client and parent. Existing tokens SHALL remain standalone and retain existing permissions without a data backfill.

#### Scenario: Upgrade an existing installation
- **WHEN** the updater runs on a database containing existing token rows
- **THEN** the new relationship metadata defaults those rows to standalone tokens and leaves their permission rows unchanged

#### Scenario: Run updater more than once
- **WHEN** the updater is re-executed after the columns and index already exist
- **THEN** the guarded migration completes without failing or duplicating schema objects
