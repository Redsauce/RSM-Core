## Overview

The current `IQ_getFilteredItemsIDs()` query joins property tables for both filters and returned columns. This change introduces an optimized path in `getFilteredItemsIDs()` that separates ID selection from property hydration when the request does not need return-property joins for ordering.

## Approach

- Add an ID-only query helper that reuses the existing filter construction logic but does not append return-property joins.
- Add a hydration helper that receives the matched item IDs and requested return properties, then fetches each property in a batched query scoped by `RS_CLIENT_ID`, `RS_ITEMTYPE_ID`, `RS_PROPERTY_ID`, and `RS_ITEM_ID IN (...)`.
- Preserve missing-property behavior by initializing every requested property as an empty string for each item before applying hydrated values.
- Preserve identifier translation by collecting the same `propertiesToTranslate` metadata and calling `_translateIds()` after hydration.
- Preserve the existing `IQ_getFilteredItemsIDs()` path for:
  - explicit ordering by a return property;
  - future edge cases where exact SQL ordering cannot be reproduced safely after hydration.

## Risks

- Ordering by hydrated property values must not silently change behavior. The optimized path should only run when ordering does not depend on return-property joins.
- Very large ID lists can produce large `IN (...)` clauses. The first implementation should keep this scoped to the same paths currently materializing arrays and can be chunked later if needed.
- Existing XML temporary-file optimization is tied to mysqli result objects. The optimized array-hydration path should initially avoid replacing that file path unless compatibility is verified.

## Verification

- Run `php -l` on changed PHP files.
- Run `php -d error_reporting=E_ALL -d display_errors=1 scripts/php_compatibility_suite.php`.
- Add or run a focused regression script that compares optimized and legacy output for representative filters, IDs, identifier translation, file/image formatting, and missing properties when local test data is available.
