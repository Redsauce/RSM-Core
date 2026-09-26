## ADDED Requirements

### Requirement: Canonical item type parameter
The item-properties filter endpoint SHALL accept `itemTypeID` as the canonical item type request parameter.

#### Scenario: Canonical parameter is supplied
- **WHEN** a valid request supplies `itemTypeID`
- **THEN** the endpoint returns the properties visible to the requested user for that item type

### Requirement: Temporary legacy parameter compatibility
The item-properties filter endpoint SHALL accept `itemtypeID` as a temporary compatibility fallback until it is removed after RSM 7.

#### Scenario: Only the legacy parameter is supplied
- **WHEN** a valid request supplies `itemtypeID` and omits `itemTypeID`
- **THEN** the endpoint processes the legacy value as the requested item type

#### Scenario: Both parameter spellings are supplied
- **WHEN** a request supplies both `itemTypeID` and `itemtypeID`
- **THEN** the endpoint uses the canonical `itemTypeID` value

### Requirement: Invalid requests do not execute malformed SQL
The endpoint MUST reject requests that do not contain valid user, client, and item type identifiers without constructing a property query from missing values.

#### Scenario: Both item type parameters are absent
- **WHEN** a request omits both `itemTypeID` and `itemtypeID`
- **THEN** the endpoint returns its existing unsuccessful XML result instead of an HTTP 500

### Requirement: Query failures do not cause result dereference fatals
The property lookup MUST verify database query results before reading rows.

#### Scenario: Property query fails
- **WHEN** the database helper returns `false` for a property or list query
- **THEN** the code does not call `fetch_assoc()` on that value
