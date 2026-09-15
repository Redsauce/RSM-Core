## Overview

Use a plain PHP array as a metadata cache for one `getFilteredItemsIDs()` execution. The wrapper creates the array, passes it to the ID-only query builder and the hydration helper, and then lets it disappear when the call returns.

## Cached Values

- property type by `(clientID, propertyID)`
- client property default value by `(clientID, propertyID)`
- main property ID by `(clientID, itemTypeID)`

The cache is intentionally not global. Some endpoints can update metadata and then continue executing in the same PHP request; a global request cache could return stale values in those flows.

## Behavior

The cached helpers call the existing metadata functions on misses and return exactly the same values. Empty strings and zero values are cached too, because misses can be valid outcomes and should not be re-queried repeatedly.

## Verification

- Extend focused regression tests to count repeated metadata lookups.
- Run PHP lint and compatibility suite.
- Run the local MariaDB benchmark to compare query counts and timings.

## Rollback

Remove the local cached helper functions and pass-through cache parameters. No stored data or schema changes are involved.
