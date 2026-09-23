## ADDED Requirements

### Requirement: Authenticated next-integer assignment
The system SHALL expose an authenticated API v2 POST endpoint that calculates and writes the next positive value for a requested item property.

#### Scenario: First unscoped value
- **WHEN** an authorized caller requests assignment for a valid empty integer property and no item of that type has a positive value for the property
- **THEN** the endpoint SHALL write `1` to the requested item property and return the assigned value

#### Scenario: Subsequent unscoped value
- **WHEN** an authorized caller requests assignment without year or series scope and the last numbered item has value `41`
- **THEN** the endpoint SHALL write and return `42`

#### Scenario: Latest item resets the sequence value
- **WHEN** the numbered items in item order contain `1`, `2`, `3`, `4`, and `1`
- **THEN** the endpoint SHALL write and return `2`, even though an earlier item contains `4`

#### Scenario: Application property name
- **WHEN** the caller supplies a mapped application property name instead of a numeric client property ID
- **THEN** the endpoint SHALL resolve it for the token client and use the resolved property

### Requirement: Optional year scope
The endpoint SHALL support an optional year scope defined by a `year` and `yearPropertyID` pair.

#### Scenario: Year-scoped sequence
- **WHEN** the request specifies year `2026`, the last target-property item dated in 2026 has value `12`, and a larger value exists on an earlier item outside 2026
- **THEN** the endpoint SHALL write and return `13`

#### Scenario: Year omitted
- **WHEN** neither `year` nor `yearPropertyID` is supplied
- **THEN** the endpoint SHALL apply no date restriction

#### Scenario: Incomplete year scope
- **WHEN** exactly one of `year` and `yearPropertyID` is supplied
- **THEN** the endpoint SHALL reject the request without writing

#### Scenario: Invalid year property
- **WHEN** `yearPropertyID` does not resolve to a date or datetime property belonging to the target item type
- **THEN** the endpoint SHALL reject the request without writing

### Requirement: Optional series scope
The endpoint SHALL support an optional series scope defined by a `series` and `seriesPropertyID` pair.

#### Scenario: Series-scoped sequence
- **WHEN** the request specifies a series, the last target-property item with that exact series has value `7`, and a larger value exists on an earlier item in another series
- **THEN** the endpoint SHALL write and return `8`

#### Scenario: Series omitted
- **WHEN** neither `series` nor `seriesPropertyID` is supplied
- **THEN** the endpoint SHALL apply no series restriction

#### Scenario: Incomplete series scope
- **WHEN** exactly one of `series` and `seriesPropertyID` is supplied
- **THEN** the endpoint SHALL reject the request without writing

#### Scenario: Invalid series property
- **WHEN** `seriesPropertyID` is not equality-filterable or does not belong to the target item type
- **THEN** the endpoint SHALL reject the request without writing

### Requirement: Combined scopes
When both optional scopes are supplied, the endpoint SHALL select the last numbered item in the intersection of the requested year and series.

#### Scenario: Year and series intersection
- **WHEN** year `2026` and series `A` are supplied and the last target-property item in their intersection has value `25`
- **THEN** the endpoint SHALL write and return `26`, regardless of larger values in other years or series

### Requirement: Target validation
The endpoint SHALL validate the target property and item before calculating or writing a number.

#### Scenario: Non-integer target property
- **WHEN** `propertyID` resolves to a property whose type is not `integer`
- **THEN** the endpoint SHALL reject the request without writing

#### Scenario: Unknown target property
- **WHEN** `propertyID` cannot be resolved for the token client
- **THEN** the endpoint SHALL reject the request without writing

#### Scenario: Item does not exist in inferred type
- **WHEN** `itemID` does not identify an item belonging to the target property's item type
- **THEN** the endpoint SHALL reject the request without writing

#### Scenario: Number already assigned
- **WHEN** the target item property already contains a positive integer
- **THEN** the endpoint SHALL return a conflict and SHALL preserve the existing value

#### Scenario: Zero value is assignable
- **WHEN** the target item property contains `0` and all other validation succeeds
- **THEN** the endpoint SHALL calculate and write the next value

### Requirement: Authorization and customer isolation
The endpoint SHALL enforce API v2 write authorization and token customer scope before reading sequence data or modifying the target item.

#### Scenario: Missing write access
- **WHEN** the caller lacks effective write access to the target property
- **THEN** the endpoint SHALL return a forbidden response without calculating or writing a value

#### Scenario: Missing scope read access
- **WHEN** the caller lacks effective read access to a supplied year or series property
- **THEN** the endpoint SHALL return a forbidden response without calculating or writing a value

#### Scenario: Customer-scoped token targets another customer
- **WHEN** a customer-scoped token requests an item outside its permitted customer scope
- **THEN** the endpoint SHALL return a forbidden response without writing

#### Scenario: Customer-scoped sequence
- **WHEN** an authorized customer-scoped token requests a valid assignment
- **THEN** the last-item calculation and sequence lock SHALL include only items belonging to that token customer

#### Scenario: Scope belongs to token client
- **WHEN** the request is valid and authorized
- **THEN** the last-item calculation SHALL include only records belonging to the client resolved from the token

### Requirement: Atomic allocation
The endpoint SHALL serialize allocation for the same client, target property, year scope, and series scope until the selected value has been persisted.

#### Scenario: Concurrent requests in the same scope
- **WHEN** two valid requests for different target items execute concurrently in the same logical sequence scope
- **THEN** both SHALL succeed with distinct consecutive values

#### Scenario: Concurrent requests in different scopes
- **WHEN** requests execute concurrently for different properties, years, or series
- **THEN** they SHALL use independent sequence locks

#### Scenario: Sequence lock unavailable
- **WHEN** the endpoint cannot acquire the sequence lock within its configured timeout
- **THEN** it SHALL return a retryable conflict or service-unavailable response without writing

### Requirement: Efficient last-item calculation
The system SHALL select the last matching numbered item in the database without loading every matching item or property value into PHP memory.

#### Scenario: Large sequence
- **WHEN** the sequence contains many thousands of matching items
- **THEN** the endpoint SHALL use a bounded ordered query and retain bounded PHP memory usage

### Requirement: Response contract
The endpoint SHALL return a JSON response describing a successful assignment and SHALL use the existing API v2 error-message policy for failures.

#### Scenario: Successful response
- **WHEN** the next value is written successfully
- **THEN** the response SHALL include the target item ID, resolved property ID, and assigned integer value

#### Scenario: Persistence failure
- **WHEN** calculation succeeds but the property write fails
- **THEN** the endpoint SHALL return an error and SHALL NOT report the number as assigned

#### Scenario: Debug disabled
- **WHEN** a request fails while API debug output is disabled
- **THEN** the endpoint SHALL avoid exposing database or internal implementation details

### Requirement: Existing invoice generation remains compatible
The four legacy number generators SHALL use the shared calculation while preserving their existing numbering scopes and request/response contracts.

#### Scenario: Existing invoice caller
- **WHEN** an existing client calls the invoice-specific PHP after deployment
- **THEN** its established request and response contract SHALL remain available

#### Scenario: Legacy assignment failure
- **WHEN** persistence of a generated number or its date fails
- **THEN** the allocation transaction SHALL roll back and the sequence lock SHALL be released without reporting success

#### Scenario: Related-item set scope
- **WHEN** a legacy generator scopes its sequence to a set of related items
- **THEN** the last-item query SHALL restrict the sequence to that set, treating an empty set as no matches
