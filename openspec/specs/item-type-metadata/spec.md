# Item type metadata

## Purpose

Describe optional property descriptions and item type selection added to API v2 getTypes in commit 4bace6c.

## Requirements

### Requirement: Optional readable property descriptions
The getTypes endpoint SHALL include `propertyDescriptions` only when `includeDescriptions` is the boolean `true`. Description keys SHALL match the corresponding `properties` keys, using mapped application names or numeric property IDs as applicable.

#### Scenario: Descriptions requested
- **WHEN** a caller requests descriptions for a readable property
- **THEN** the endpoint SHALL decode stored HTML entities and return its description, or an empty string when absent

#### Scenario: Unreadable property
- **WHEN** a property fails both token read permission and user visibility checks
- **THEN** the response SHALL NOT expose that property's description

#### Scenario: Descriptions not requested
- **WHEN** `includeDescriptions` is omitted or is not the boolean `true`
- **THEN** the response SHALL omit `propertyDescriptions`

### Requirement: Omitted type selection returns readable types
The endpoint SHALL allow the request body or its `IDs` field to be omitted and enumerate the client's item types, returning only types with at least one property readable by the caller.

#### Scenario: All readable types requested
- **WHEN** the request has no body or no `IDs` field
- **THEN** the endpoint SHALL consider all client item types and apply existing property visibility filtering

#### Scenario: Categories and descriptions requested together
- **WHEN** both metadata options are enabled
- **THEN** descriptions SHALL use the same visible property keys as the other property metadata
- **AND** categories SHALL remain limited to those containing at least one visible property
