## ADDED Requirements

### Requirement: Authenticated generic duplication
The system SHALL provide `POST /api/v2/items/duplicate.php` to duplicate one existing root item and its explicitly selected descendants within the authenticated client and the same item type. The body SHALL contain `itemTypeID`, `itemID` and optionally `descendants`; other fields SHALL be rejected. The endpoint SHALL support standard API v2 OPTIONS handling.

#### Scenario: Valid source
- **WHEN** an authorized caller supplies a valid item type and source item ID
- **THEN** the endpoint SHALL create one new item of that type and client with a fresh internal ID, leaving the source unchanged

#### Scenario: Mapped type identifier
- **WHEN** the caller supplies a mapped application item-type identifier
- **THEN** the endpoint SHALL resolve it for the token client using existing API conventions

#### Scenario: Invalid request
- **WHEN** the body is not an object, either required field is missing or invalid, itemID is not a positive integer, or unsupported fields are supplied
- **THEN** the endpoint SHALL return 400 without creating an item

#### Scenario: Source not found
- **WHEN** the source does not exist for the resolved client and item type
- **THEN** the endpoint SHALL return 404 without creating an item

#### Scenario: Preflight
- **WHEN** an OPTIONS request is received
- **THEN** the endpoint SHALL follow API v2 CORS conventions without copying an item

### Requirement: Configured property exclusion
The endpoint SHALL copy properties allowed by the resolved client's RSM configuration and SHALL exclude every property not permitted by `RS_AVOID_DUPLICATION = 0`. It SHALL NOT override this selection with business-specific rules.

#### Scenario: Excluded property has a value
- **WHEN** a source property is marked as non-duplicable and contains a value
- **THEN** the new item SHALL receive no stored value for that property from duplication, and the source value SHALL remain unchanged

#### Scenario: Excluded property has a configured default
- **WHEN** a non-duplicable property also has a configured default
- **THEN** duplication SHALL NOT explicitly insert that default, and reads SHALL retain normal RSM missing-value behavior

#### Scenario: Eligible number or date
- **WHEN** a number, date, or grouping property is configured as duplicable
- **THEN** its source value SHALL be copied without resetting, incrementing or replacing it

#### Scenario: No eligible properties
- **WHEN** all properties are excluded and the caller passes the defined authorization checks
- **THEN** the endpoint SHALL create only the new internal item identity without copying excluded values

### Requirement: Preserve eligible property content
Duplication SHALL preserve eligible property content using RSM's typed storage semantics, including text, integer, date/datetime, list, identifier, identifiers, file and image properties.

#### Scenario: Text and empty values
- **WHEN** eligible properties contain Unicode, quotes, empty values or zero
- **THEN** the copy SHALL preserve their logical values without SQL corruption or accidental omission of zero

#### Scenario: Binary property
- **WHEN** an eligible file or image exists
- **THEN** its content, name and size SHALL be copied correctly

#### Scenario: Missing binary property
- **WHEN** an eligible file or image has no stored content
- **THEN** the copy SHALL remain empty for that property without causing malformed SQL or a partial copy

### Requirement: Single-item reference behavior
Without selected dependencies, the endpoint SHALL copy eligible reference values and ordering without duplicating referenced items or descendants. It SHALL NOT apply the reference document endpoint's concept-copying or field-clearing rules.

#### Scenario: Related items
- **WHEN** the source references another item through a duplicable property
- **THEN** the copy SHALL reference the same item, and no additional related item SHALL be created

#### Scenario: Incoming child relation
- **WHEN** child items reference the source and their dependency relation is not selected
- **THEN** those items SHALL remain unchanged and SHALL NOT be duplicated or reparented

### Requirement: Authorization and tenant isolation
The endpoint SHALL derive client identity from authentication, require effective READ and CREATE access for every eligible property, and enforce existing customer-token scope for both source and copy. Missing permissions SHALL reject the entire request rather than produce a permission-filtered partial copy. For a type with no eligible properties, the endpoint SHALL require effective READ and CREATE access on its configured main property or reject if no usable permission anchor exists.

#### Scenario: Same ID in another client or type
- **WHEN** other clients or item types contain the same numeric item ID
- **THEN** only the source tuple resolved for the authenticated client and requested type SHALL be copied

#### Scenario: Missing property access
- **WHEN** the caller lacks effective READ or CREATE access on an eligible property
- **THEN** the endpoint SHALL return 403 without creating any copy

#### Scenario: Source outside customer scope
- **WHEN** a customer-scoped token requests a source outside its scope
- **THEN** the endpoint SHALL return 403 without copying it

#### Scenario: Scope dependency excluded
- **WHEN** non-duplication configuration excludes a dependency necessary to keep the copy within the token's customer scope
- **THEN** the endpoint SHALL return 403 without copying or forcibly filling the excluded property

### Requirement: Atomic copy and identity allocation
The endpoint SHALL use the shared duplication implementation and SHALL return success only after the new item, eligible values and allocation metadata are persisted. Failed copies SHALL be rolled back and SHALL NOT alter the source or overwrite existing items.

#### Scenario: Property persistence failure
- **WHEN** any copied property or allocation-metadata write fails
- **THEN** the endpoint SHALL return an error and roll back the new item and all partial copied values

#### Scenario: Concurrent copies
- **WHEN** requests concurrently duplicate items of the same type
- **THEN** successful copies SHALL have distinct internal IDs, and an allocation conflict SHALL be retried safely or returned as a clean failure without a partial copy

### Requirement: Response and compatibility
A successful request SHALL return HTTP 200 with numeric `itemTypeID`, `sourceItemID` and `newItemID`. Errors SHALL follow existing API v2 authentication and debug-disclosure conventions. Existing document duplication and next-integer endpoint contracts SHALL remain unchanged.

#### Scenario: Successful response
- **WHEN** source item 123 of type 98 is successfully copied to new item 200
- **THEN** the response SHALL be `{"itemTypeID":98,"sourceItemID":123,"newItemID":200}`

#### Scenario: Repeated request
- **WHEN** the same valid request is submitted again after success
- **THEN** a separate new copy SHALL be created with another fresh internal ID

#### Scenario: Existing document caller
- **WHEN** a caller uses the existing document duplication PHP
- **THEN** its existing request, response and document-specific behavior SHALL remain available

### Requirement: Explicit dependent graph
The optional `descendants` field SHALL be an array of objects containing only `itemTypeID` and `dependencyPropertyID`. Each property SHALL be an eligible identifier or identifiers property belonging to the supplied dependent type. Its referred type SHALL be reachable from the root through selected edges. The endpoint SHALL reject invalid or disconnected edges with 400, without creating copies. Mapped type identifiers SHALL be supported. The shared copier's configured recursive relation for a selected dependent type SHALL also be validated and followed.

#### Scenario: Invoice and concepts
- **WHEN** the caller selects the concept-to-invoice dependency
- **THEN** the invoice and its existing concepts SHALL be copied and new concepts SHALL reference the new invoice
- **AND** unrelated concepts and other clients' items SHALL remain unchanged

#### Scenario: Chains and shared children
- **WHEN** selected dependencies form chains or reach a child through multiple parents or paths
- **THEN** every reachable exclusive source tuple SHALL be copied exactly once; shared children SHALL follow the reuse rule below
- **AND** selected references to copied parents SHALL point to their copies, preserving external references and using the existing setters' ordering semantics

#### Scenario: Cyclic or self dependency
- **WHEN** explicitly selected relations form a cycle or self dependency
- **THEN** traversal SHALL terminate, each source SHALL be copied once and selected references SHALL point to the corresponding copies

#### Scenario: Excluded dependency
- **WHEN** a selected dependency is excluded by duplication configuration
- **THEN** the request SHALL fail with 400 without overriding configuration

#### Scenario: Child access or persistence failure
- **WHEN** any selected type lacks required READ/CREATE access, any source or destination fails customer scope, or any discovery/copy/remapping operation fails
- **THEN** the entire operation SHALL fail with no persisted copies or allocation changes

#### Scenario: Extended response
- **WHEN** the request includes `descendants`
- **THEN** the root response SHALL additionally contain `copiedItems`, an array of numeric `{itemTypeID, sourceItemID, newItemID}` mappings including the root
- **AND** an empty array SHALL copy only the root, whereas omission SHALL preserve the original response shape

### Requirement: Reuse shared children
A child whose selected identifiers dependency contains multiple distinct parent IDs SHALL retain its identity. Duplication SHALL append the copied parent IDs to that existing relation while retaining the original parents. It SHALL NOT copy that child or traverse its subtree through the shared relation. Reused items SHALL NOT appear in copiedItems.

#### Scenario: Child belongs to several parents
- **WHEN** a child references parents A and B and A is duplicated as A2
- **THEN** the existing child SHALL reference A, B and A2 and no copy of that child SHALL be created

#### Scenario: Several copied parents
- **WHEN** multiple parents or multiple copies of a parent are created
- **THEN** all new parent IDs SHALL be appended to the shared child's list exactly once

#### Scenario: Exclusive child
- **WHEN** an identifiers relation contains only one parent
- **THEN** the child SHALL be copied normally and the copied relation SHALL reference the new parent

#### Scenario: Shared-child authorization and rollback
- **WHEN** WRITE access on the shared relation or customer scope is denied, or persistence fails
- **THEN** the complete request SHALL fail and roll back both created items and modifications to existing shared children
