## Tasks

- [x] Add an ID-only filtered query helper that excludes return-property joins.
- [x] Add a batched return-property hydration helper with missing-value compatibility.
- [x] Route safe `getFilteredItemsIDs()` calls through the two-phase path while preserving legacy fallback cases.
- [x] Enable the two-phase path for large file-result responses by writing hydrated arrays to temporary XML.
- [x] Remove legacy fallback routing from `getFilteredItemsIDs()` for order and return-order requests.
- [x] Add regression tests that guard optimized routing, order, return-order, hydration, translation, and XML paths.
- [x] Add and run MariaDB differential tests with synthetic random data comparing legacy HEAD and current optimized responses.
- [x] Add and run local MariaDB benchmark comparing legacy HEAD and current optimized response times.
- [x] Re-run syntax, compatibility, and OpenSpec validation after removing legacy fallback.
- [x] Add simple comments to the optimized query, hydration, XML, and test changes.
