## 1. Endpoint compatibility

- [x] 1.1 Resolve canonical `itemTypeID` with a documented legacy `itemtypeID` fallback and post-RSM-7 removal TODO
- [x] 1.2 Validate required numeric request identifiers before querying

## 2. Query robustness

- [x] 2.1 Guard property and list query results before calling `fetch_assoc()`

## 3. Verification

- [x] 3.1 Add focused compatibility regression coverage for canonical, legacy, precedence, and missing-parameter behavior
- [x] 3.2 Run PHP lint and focused compatibility tests
