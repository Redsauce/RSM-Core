# Item write integrity

## Purpose

Describe shared property writes, item creation, CSV imports and global variable updates implemented in commits cdd43d2, 2182282, 4e32636, d4c0a1e, ba60db5, 563ee5d, 55f0c8a and 5f13cd0.

## Requirements

### Requirement: Validated binary property transport
File and image writes SHALL validate the legacy `[filename:]hexadecimal` representation before decoding or storing it. An optional `0x` prefix SHALL be accepted. Invalid input SHALL be rejected rather than replaced with a property default.

#### Scenario: Empty binary value
- **WHEN** a file or image value is an empty string
- **THEN** the writer SHALL accept an empty name and zero-byte content

#### Scenario: Malformed binary input
- **WHEN** input has an invalid filename, odd hexadecimal length or non-hexadecimal content
- **THEN** the writer SHALL reject it before storage

#### Scenario: Configured binary size limit
- **WHEN** decoded content exceeds `RSmaxBinaryPropertyBytes`, which defaults to 32 MiB
- **THEN** the writer SHALL reject the value and SHALL NOT silently truncate it

### Requirement: Prepared property and audit writes
Shared property insertion and binary replacement SHALL bind values in prepared statements. Binary content SHALL be sent as binary data, including zero-byte content. Creation audit records SHALL explicitly supply the required initial and final values.

#### Scenario: Scalar property creation is audited
- **WHEN** a newly inserted non-binary property has auditing enabled
- **THEN** its audit record SHALL use the property's type-level empty value as `RS_INITIAL_VALUE` and the stored value as `RS_FINAL_VALUE`
- **AND** these values SHALL be bound parameters rather than SQL expressions derived from input

#### Scenario: Binary property creation is audited
- **WHEN** a newly inserted file or image property has auditing enabled
- **THEN** its audit record SHALL contain an empty initial name, zero initial size and empty initial content, together with the final name, size and content

### Requirement: Transaction-aware item creation
Ordinary `createItem` calls SHALL start a transaction, lock the client item type, create the item and its properties, and commit only after all required writes succeed. Callers explicitly owning a batch transaction SHALL be able to disable internal transaction management.

#### Scenario: Property omitted from the request
- **WHEN** a property defined on the item type is absent from the creation payload
- **THEN** creation SHALL obtain its configured default and attempt to store that property too

#### Scenario: Creation fails
- **WHEN** an item, counter, property or required audit write fails
- **THEN** internally managed creation SHALL roll back, restore the previous created-item tracking state and return `0`
- **AND** the server log SHALL include the caught exception class, message, source basename and line

#### Scenario: Property writer returns failure
- **WHEN** a property writer returns failure without throwing an exception
- **THEN** the creation diagnostic SHALL identify the property ID and type

### Requirement: Commands stop after unsuccessful creation
The tree-list creation command and shared item, concept and operation creation commands SHALL check the ID returned by `createItem` before using it.

#### Scenario: Invalid creation ID
- **WHEN** creation returns zero or a negative ID
- **THEN** the command SHALL return a controlled `NOK` response instead of continuing with that ID
- **AND** shared multi-item creation SHALL report IDs already created before the failure without claiming the whole request was rolled back

### Requirement: Transactional CSV imports
CSV imports SHALL validate identifiers, row shape, Base64 input and property values before writes, lock the item type, and write the batch in one transaction through the shared property writer. Imports SHALL preserve the existing behavior of not creating property audit records.

#### Scenario: Overwrite preserves omitted properties
- **WHEN** overwrite mode is used and a property is not among the imported columns
- **THEN** the importer SHALL leave its stored value unchanged

#### Scenario: Import failure
- **WHEN** a batch write fails
- **THEN** the importer SHALL roll back the batch and return a controlled import error without exposing SQL query details

#### Scenario: Import succeeds
- **WHEN** all imported items and properties are stored
- **THEN** the importer SHALL advance the item counter without moving it backwards and commit
- **AND** it SHALL attempt to invalidate caches for imported file and image properties after commit

### Requirement: Validated global variable updates
Global variable updates SHALL validate names, types and image payloads before removing existing variables, and SHALL use prepared writes in one transaction.

#### Scenario: Global update failure
- **WHEN** a database write fails during a global update
- **THEN** the helper SHALL roll back and return a controlled failure

#### Scenario: Oversized request response
- **WHEN** the centralized HTTP error handler receives status 413
- **THEN** it SHALL return HTTP 413 with the corresponding payload-too-large message
