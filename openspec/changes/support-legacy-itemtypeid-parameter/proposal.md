## Why

The legacy item-properties filter endpoint historically accepted `itemtypeID`, while current clients and constants use `itemTypeID`. A mixed deployment currently turns the missing legacy key into malformed SQL and an HTTP 500 response.

## What Changes

- Treat `itemTypeID` as the canonical request parameter.
- Temporarily accept the historical `itemtypeID` spelling as a compatibility fallback.
- Document in code that the fallback is temporary and add a TODO to remove it after RSM 7.
- Reject a request with neither spelling without constructing an invalid SQL query.
- Avoid calling result methods when the underlying property query fails.

## Capabilities

### New Capabilities

- `legacy-itemtype-parameter-compatibility`: Compatibility behavior for legacy item-properties requests during the RSM 7 transition.

### Modified Capabilities


## Impact

- `itemsManager/classLbxItemPropertiesFilter_getProperties.php`
- `utilities/RSMuserPropertiesManagement.php`
- Legacy Redsauce Manager clients and mixed-version RSM deployments.
