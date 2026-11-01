## ADDED Requirements

### Requirement: Optional pagination across item-list endpoints

The system SHALL support zero-based `offset` with maximum-count `limit` on Data Manager `shared/classDataManager_getItems.php`, API v1 `api/api_getItems.php`, and API v2 `api/v2/items/get.php`. Offset SHALL count entries skipped in the authorized, filtered, ordered result, not item IDs. The endpoints SHALL retain their existing request transports.

#### Scenario: First and subsequent pages
- **WHEN** a client requests offset 0 and limit 100, then offset 100 and limit 100, against an unchanged result with at least 200 entries
- **THEN** the pages contain positions 1-100 and 101-200 respectively with no overlap

#### Scenario: Inclusive human-readable range
- **WHEN** a client requests offset 1 and limit 499
- **THEN** it receives at most the entries in positions 2 through 500 inclusive

#### Scenario: API v2 count without offset
- **WHEN** an API v2 client supplies limit 100 and omits offset
- **THEN** pagination starts at offset zero and returns at most 100 items

### Requirement: Backward-compatible opt-in behavior

Data Manager and v1 SHALL retain historical limit-only behavior when offset is absent or an empty form value. V2 SHALL preserve its existing behavior when both pagination parameters are absent. Shared function extensions SHALL preserve existing positional arguments, named arguments and default behavior. New-mode pagination SHALL NOT alter response envelopes, property naming, category grouping, translations, compression or temporary-file cleanup.

#### Scenario: Existing caller without offset
- **WHEN** an existing Data Manager or v1 caller omits offset or sends an empty form offset
- **THEN** its existing limit interpretation, validation, ordering and response format remain unchanged

#### Scenario: Legacy composite limit
- **WHEN** Data Manager receives a previously supported composite limit string without a nonempty offset
- **THEN** that legacy behavior is preserved
- **AND** v1 retains its existing rejection of composite limit strings

#### Scenario: Unpaginated API v2 caller
- **WHEN** a v2 request omits limit and offset
- **THEN** it follows the current unpaginated contract with the same output options and empty-result representation

### Requirement: Validated offset and count

In new pagination mode the system SHALL accept only nonnegative integers or unsigned decimal integer strings representable in the runtime integer range. Offset SHALL require a nonempty limit; v2 limit alone SHALL imply offset zero. Malformed values SHALL produce the endpoint's established validation error before the item query; APIs SHALL use HTTP 400. The new mode SHALL NOT silently coerce invalid input into another page.

#### Scenario: Invalid numeric values
- **WHEN** new-mode offset or limit is negative, fractional, boolean, null, an array/object, whitespace-only, exponent notation, an SQL fragment or outside the supported integer range
- **THEN** the request fails validation without executing the item query

#### Scenario: Missing count
- **WHEN** a nonempty form offset or a present JSON offset is provided without a valid limit
- **THEN** the request fails with the endpoint's established validation error

#### Scenario: Empty JSON pagination value
- **WHEN** v2 receives an empty string as offset or limit
- **THEN** the request fails validation rather than falling back to an unpaginated read

### Requirement: Deterministic pagination order

New-mode pagination SHALL default to ascending item ID. When a supported custom property order is supplied, the system SHALL preserve it and append ascending item ID as the unique tie-breaker. Legacy calls SHALL retain their prior ordering. The system SHALL document that pages do not constitute a snapshot across concurrent modifications.

#### Scenario: Equal property values
- **WHEN** a paginated Data Manager or v1 request orders by a supported property shared by multiple items
- **THEN** those tied items are ordered by ascending item ID and successive pages remain consistent for an unchanged result set

#### Scenario: Default order in v2
- **WHEN** a v2 request activates pagination without an ordering option
- **THEN** entries are selected in ascending item ID order

### Requirement: Page IDs before loading large properties

For new-mode requests without external filters, the system SHALL apply tenant and token scope, ordinary filters, explicit IDs, ordering and SQL offset/count in the ID-selection phase, and SHALL hydrate, translate and serialize only the selected page. It SHALL NOT load the large properties of skipped or subsequent entries to implement offset. SQL traversal or sorting of preceding candidate IDs is permitted.

#### Scenario: Deep offset with variants
- **WHEN** a request selects offset 999 and limit 1 without external filters
- **THEN** PHP loads properties only for the item at position 1,000 if present
- **AND** it does not hydrate variants of the first 999 entries

#### Scenario: Explicit IDs and scope
- **WHEN** a paginated request includes explicit IDs, ordinary filters and customer token scope
- **THEN** the page is selected from their authorized intersection and no out-of-scope data is returned

### Requirement: Page boundaries and empty results

The system SHALL return at most limit entries, a shorter page when fewer remain, and the endpoint's existing empty-result representation when offset exceeds the result count or limit is zero. A zero-count page SHALL NOT hydrate candidate properties after normal validation and authorization.

#### Scenario: Last short page
- **WHEN** 230 entries match and offset is 200 with limit 100
- **THEN** the endpoint returns the remaining 30 entries

#### Scenario: Offset beyond the end
- **WHEN** offset is at or beyond the matching entry count
- **THEN** the endpoint returns its existing empty-result representation without changing the wire contract

#### Scenario: Zero count
- **WHEN** a valid authorized new-mode request specifies limit zero
- **THEN** it returns the normal empty-result representation without hydrating candidate properties

### Requirement: External filters precede page slicing

For new-mode requests with external filters, the system SHALL apply offset/count to the result after external filtering, without applying the page twice or filtering twice. It SHALL document that this path can require full candidate hydration and does not provide the page-only loading optimization. The legacy path SHALL remain unchanged.

#### Scenario: Early candidates rejected by external rules
- **WHEN** an external rule removes initial candidates and the client requests offset 1 and limit 2
- **THEN** the response contains the second and third surviving authorized items in deterministic order
- **AND** it does not slice SQL candidates before external filtering

### Requirement: Discoverable endpoint comments and examples

All three affected endpoint source files SHALL describe the implemented offset/count parameters and contain credential-free examples for first and next pages and positions 2-500 using their actual accepted field names and transport. Comments SHALL distinguish result positions from IDs, state backward compatibility, short/empty-page behavior, ordering and concurrent-change caveats, and external-filter costs. Shared-helper documentation SHALL explain optional pagination arguments and page-before-hydration behavior.

#### Scenario: Source-reading client discovers pagination
- **WHEN** a developer or AI agent reads the parameter header and examples of any affected endpoint
- **THEN** it can construct valid first-page and subsequent-page requests without assuming a new response envelope or confusing limit with an ending position
- **AND** v2 examples are valid JSON using the existing JSON-body contract
