## ADDED Requirements

### Requirement: Authenticated next-integer assignment
The system SHALL expose an authenticated API v2 POST endpoint that calculates and writes the next positive value for a requested item property.

#### Scenario: First value with no period history
- **WHEN** an authorized caller requests assignment and neither the current UTC year nor any earlier year contains a positive value in the requested sequence
- **THEN** the endpoint SHALL write `1` and the current UTC date to the requested item

#### Scenario: Maximum determines the next value
- **WHEN** the selected period contains `1`, `2`, `3`, `4`, and `1`
- **THEN** the endpoint SHALL write and return `5`, independently of item order

#### Scenario: Application property name
- **WHEN** the caller supplies a mapped application property name instead of a numeric client property ID
- **THEN** the endpoint SHALL resolve it for the token client and use the resolved property

### Requirement: Required UTC period property
The endpoint SHALL require a `filterPropertyID` identifying a date property, derive the active period from the current UTC date, and write that date without accepting a caller-supplied year or date.

#### Scenario: Current period has values
- **WHEN** the current UTC year contains values `10`, `14`, and `12`
- **THEN** the endpoint SHALL write number `15` and today's UTC date

#### Scenario: Current period is empty
- **WHEN** the current UTC year contains no numbered items and the newest earlier populated year has maximum `111`
- **THEN** the endpoint SHALL write number `112` and today's UTC date

#### Scenario: Several periods are empty
- **WHEN** the current year and one or more immediately preceding years are empty
- **THEN** the endpoint SHALL continue searching backwards and use the maximum from the newest earlier populated year

#### Scenario: Earlier period has a larger value
- **WHEN** the current year has maximum `3` and an earlier year has maximum `900`
- **THEN** the endpoint SHALL write `4` because fallback applies only when the current period is empty

#### Scenario: Invalid filter property
- **WHEN** `filterPropertyID` is missing, is not a date property, or does not belong to the target item type
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
When an optional series is supplied, the endpoint SHALL calculate the rolling annual sequence inside that series.

#### Scenario: Current period and series intersection
- **WHEN** the current UTC year and series `A` have maximum `25`
- **THEN** the endpoint SHALL write and return `26`, regardless of larger values in other series

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
- **WHEN** the target item property contains `0`, its filter date is empty, and all other validation succeeds
- **THEN** the endpoint SHALL calculate and write the next value

#### Scenario: Filter date already assigned
- **WHEN** the target item already contains a filter date
- **THEN** the endpoint SHALL return a conflict and preserve both target properties

### Requirement: Authorization and customer isolation
The endpoint SHALL enforce API v2 write authorization and token customer scope before reading sequence data or modifying the target item.

#### Scenario: Missing write access
- **WHEN** the caller lacks effective write access to the target property
- **THEN** the endpoint SHALL return a forbidden response without calculating or writing a value

#### Scenario: Missing scope access
- **WHEN** the caller lacks effective read or write access to the required filter property, or read access to a supplied series property
- **THEN** the endpoint SHALL return a forbidden response without calculating or writing a value

#### Scenario: Customer-scoped token targets another customer
- **WHEN** a customer-scoped token requests an item outside its permitted customer scope
- **THEN** the endpoint SHALL return a forbidden response without writing

#### Scenario: Customer-scoped sequence
- **WHEN** an authorized customer-scoped token requests a valid assignment
- **THEN** the maximum calculation and sequence lock SHALL include only items belonging to that token customer

#### Scenario: Scope belongs to token client
- **WHEN** the request is valid and authorized
- **THEN** the maximum calculation SHALL include only records belonging to the client resolved from the token

### Requirement: Atomic allocation
The endpoint SHALL serialize allocation for the same client, target property, filter property, series scope, and customer scope until the selected number and UTC date have been persisted.

#### Scenario: Concurrent requests in the same scope
- **WHEN** two valid requests for different target items execute concurrently in the same logical sequence scope
- **THEN** both SHALL succeed with distinct consecutive values

#### Scenario: Concurrent requests in different scopes
- **WHEN** requests execute concurrently for different target properties, filter properties, series, or customers
- **THEN** they SHALL use independent sequence locks

#### Scenario: Sequence lock unavailable
- **WHEN** the endpoint cannot acquire the sequence lock within its configured timeout
- **THEN** it SHALL return a retryable conflict or service-unavailable response without writing

### Requirement: Efficient period-maximum calculation
The system SHALL calculate the maximum for the newest eligible period in the database without loading every matching item or property value into PHP memory.

#### Scenario: Large sequence
- **WHEN** the sequence contains many thousands of matching items
- **THEN** the endpoint SHALL use an aggregate query that selects at most one period and retain bounded PHP memory usage

### Requirement: Response contract
The endpoint SHALL return a JSON response describing a successful assignment and SHALL use the existing API v2 error-message policy for failures.

#### Scenario: Successful response
- **WHEN** the next value is written successfully
- **THEN** the response SHALL include the target item ID, resolved integer and filter property IDs, assigned integer, and UTC date

#### Scenario: Persistence failure
- **WHEN** calculation succeeds but the property write fails
- **THEN** the endpoint SHALL return an error, roll back the transaction, and leave the target number unchanged

#### Scenario: Audit persistence failure
- **WHEN** either property or either audit-trail write fails
- **THEN** the endpoint SHALL return an error and roll back both generated values before releasing the sequence lock

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
- **THEN** the maximum query SHALL restrict the sequence to that set, treating an empty set as no matches
