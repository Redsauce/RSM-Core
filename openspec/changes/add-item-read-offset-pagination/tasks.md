## 1. Shared pagination contract

- [x] 1.1 Add opt-in pagination parsing/validation for unsigned integers and decimal strings, with overflow protection, count-zero support and endpoint-specific error mapping; preserve the absent-offset legacy path.
- [x] 1.2 Extend shared reader/query helpers using trailing optional arguments or equivalent explicit options, preserving existing positional and named callers.
- [x] 1.3 Apply validated offset/count in the ID-selection SQL before property hydration, retaining filters, explicit IDs and token/tenant scope.
- [x] 1.4 Add default item-ID ordering and an ID tie-breaker for existing supported property orders in new mode only.
- [x] 1.5 Handle external filters by filtering the deterministically ordered candidate set before slicing once; preserve serialization, translations and legacy behavior, and avoid duplicate filter execution.
- [x] 1.6 Return count-zero and beyond-end pages through existing empty-response paths without unnecessary property hydration.

## 2. Endpoint integration

- [x] 2.1 Accept optional form offset in Data Manager `shared/classDataManager_getItems.php`, preserving old limit-only strings and existing error/response conventions.
- [x] 2.2 Accept optional form offset in API v1 `api/api_getItems.php`, keeping current limit-only validation and XML output behavior.
- [x] 2.3 Read and validate optional limit/offset in API v2 `api/v2/items/get.php` and forward pagination instead of always passing an empty limit; retain existing methods, JSON-body transport and output options.

## 3. Source documentation and examples

- [x] 3.1 Update the Data Manager parameter header and add credential-free examples for offset 0/limit 100, offset 100/limit 100 and offset 1/limit 499 with the endpoint's real form field names.
- [x] 3.2 Add equivalent parameter comments and examples to the v1 endpoint using propertyIDs/filterRules field names.
- [x] 3.3 Add equivalent v2 header documentation and valid JSON examples using itemTypeID or propertyIDs plus numeric limit/offset, retaining its JSON-body contract.
- [x] 3.4 Document count versus end position, offset versus item ID, omitted-parameter compatibility, default/stable order, short/empty pages, concurrent-change caveats and the external-filter materialization exception in all three source files; document shared helper arguments and SQL-before-hydration behavior.

## 4. Regression and validation

- [x] 4.1 Add focused tests for all three endpoint adapters and validation: absent/empty form offset, v2 count-only, offset/count zero, first/next/short/beyond-end pages, missing count, malformed types and integer overflow.
- [x] 4.2 Verify no-parameter and historical limit-only requests retain prior ordering, validation and XML/JSON output contracts, including Data Manager composite limits, v1 rejection of composite limits and existing shared callers.
- [x] 4.3 Verify deterministic property-order ties, default ID order, explicit IDs and token customer scope against an unchanged fixture.
- [x] 4.4 Verify a deep-offset variant request hydrates only the selected IDs rather than properties from skipped entries; include variant file output and v2 category/system-name/translation options.
- [x] 4.5 Verify external filters run before page slicing and exactly once, including when early candidates are rejected and file-backed serialization is used.
- [x] 4.6 Run syntax checks and relevant existing item-query, token-scope, variant-memory and response-file regression scripts; validate source examples against accepted request formats and record results.

## Validation results

- PHP syntax checks passed for all affected endpoints, shared helpers and test files.
- `scripts/test_item_read_pagination.php`: 159 pagination checks plus 170 existing token-scope checks, using an isolated SQLite fixture with sqlite3/mbstring enabled. Covers actual endpoint adapters, page-only property hydration, external-filter sequencing, legacy calls, output options and all nine documented page examples.
- `scripts/test_dynamic_item_joins.php`: passed.
- `scripts/test_variant_response_memory.php` with `memory_limit=32M`: passed; 1,201 rows, 78,936,094 XML bytes, 2,097,152-byte peak.
- `scripts/test_response_file_threshold.php`: 138 cases passed.
- `scripts/test_response_temp_cleanup.php`: 13 cases passed.
- External filtering is doubled in the isolated pagination test to verify ordering and single execution; production database performance and concurrent edits are not simulated. No deployment is included in this implementation.
