## ADDED Requirements

### Requirement: Exact parent identity defines parent-type scope
For a valid customer-scoped token, the system SHALL consider an existing item of the configured customer item type inside scope if and only if its client, item type, and item ID match the token's configured parent. This parent-type rule SHALL take precedence over direct dependency requirements. Other item types SHALL retain existing direct dependency rules.

#### Scenario: Own parent has no self-reference
- **WHEN** the token targets its existing configured parent, which has no relationship to itself
- **THEN** the item MUST pass the customer-scope check without requiring a dependency property

#### Scenario: Parent has ambiguous dependency properties
- **WHEN** the exact configured parent has multiple potential customer dependency properties
- **THEN** parent scope membership MUST be determined by its identity without dependency resolution

#### Scenario: Different parent has a matching dependency
- **WHEN** the token targets a different item of its configured parent type, including one linked to the configured parent
- **THEN** the system MUST consider that item outside scope

#### Scenario: Client or type does not match
- **WHEN** a target shares the configured numeric parent ID but belongs to another client or item type
- **THEN** the parent exception MUST NOT authorize it
- **AND** another-client access MUST be denied and another-type access MUST require the existing dependency and permission checks

#### Scenario: Configured parent does not exist
- **WHEN** the configured parent cannot be found under the token's client and configured type
- **THEN** the system MUST NOT authorize a parent operation solely from matching request IDs

#### Scenario: Related items retain access
- **WHEN** the token targets an item of another type with a valid matching direct identifier or multi-identifier dependency
- **THEN** the item MUST remain inside scope subject to existing permissions

#### Scenario: Unrelated items remain denied
- **WHEN** the token targets an item of another type with missing, ambiguous, or nonmatching customer dependency
- **THEN** the system MUST deny scope membership

### Requirement: Parent access preserves existing authorization
The system SHALL enforce authentication, scope validity, operation/property permissions, and visibility in addition to parent identity in API v1 and v2. Parent identity SHALL NOT grant additional permissions.

#### Scenario: Authorized parent edit
- **WHEN** a valid token has the required update permissions and edits an allowed property on its own parent without a self-reference
- **THEN** the update MUST be allowed subject to existing payload validation

#### Scenario: Parent permission is missing
- **WHEN** a token targets its own parent but lacks the permission required for the requested read, update, delete, or property operation
- **THEN** the operation MUST be denied according to existing permission behavior

#### Scenario: Child token uses inherited permissions
- **WHEN** an authenticated child token targets its own parent
- **THEN** the system MUST use its existing master-template permission resolution and enabled-state requirements

#### Scenario: Invalid or standard token
- **WHEN** a token has partial scope metadata or fails authentication
- **THEN** the parent exception MUST NOT grant access

#### Scenario: Standard token remains unchanged
- **WHEN** an authenticated standard token has no customer scope
- **THEN** its existing authorization behavior MUST remain unchanged

### Requirement: Parent queries enforce exact identity
API v1 and v2 lists, counts, searches, feeds, and other item-backed query paths SHALL enforce the exact parent constraint when querying the configured parent type, together with existing request filters and visibility, before counting or pagination.

#### Scenario: Query matches several parents
- **WHEN** a permitted parent-type query matches the configured parent and other parents
- **THEN** only the configured parent MUST be eligible for returned data or counts

#### Scenario: Caller filter excludes the parent
- **WHEN** the caller's filters exclude the configured parent
- **THEN** the query MUST return no matching parents and a count of zero where applicable

### Requirement: Parent isolation covers all item operations
API v1 and v2 SHALL apply the same scope rule to supported direct reads, updates, deletes, properties, files, pictures, audit records, lookups, batch operations, and queued or indirect item-backed actions. Every explicitly requested item target SHALL require its own scope and permission checks. Existing automatic reference cleanup during deletion SHALL remain unchanged.

#### Scenario: Own parent file access
- **WHEN** a valid token reads or updates a file or picture belonging to its configured parent with the required permissions
- **THEN** the operation MUST pass scope validation without a self-reference

#### Scenario: Another parent is targeted
- **WHEN** a token attempts to read, mutate, delete, stream, or queue an action against another parent
- **THEN** the system MUST deny access before exposing data or performing the unauthorized action

#### Scenario: Batch includes another parent
- **WHEN** an update or delete batch contains both the configured parent and another parent
- **THEN** the entire batch MUST be rejected before any item is mutated

#### Scenario: Deletion preserves existing reference handling
- **WHEN** the token has permission to delete its configured parent and other items reference that parent
- **THEN** the deletion MUST use the existing reference cleanup behavior without adding access or WRITE-permission requirements on those other items

#### Scenario: Empty delete list is a no-op
- **WHEN** a token submits an empty ID list for an item type
- **THEN** the endpoint MUST skip that group without calling the deletion helper or rejecting the request for having no targets
- **AND** an otherwise valid request containing only empty groups MUST succeed without deleting anything
- **AND** non-empty groups MUST retain all scope and permission checks

### Requirement: Parent exception does not authorize creation
The system SHALL reject creation of new items of the configured parent type by customer-scoped tokens. Existing creation rules for other item types SHALL remain unchanged.

#### Scenario: Create another parent
- **WHEN** a scoped token submits a create request for its configured parent type, even with a dependency pointing to its own parent
- **THEN** the system MUST reject the request without creating an item

#### Scenario: Create related item
- **WHEN** a scoped token creates an item of another type
- **THEN** existing customer dependency payload handling and CREATE permission checks MUST continue to apply
