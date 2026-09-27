## Context

The legacy endpoint originally read the lowercase `itemtypeID` POST key. The repository later standardized the constant to `itemTypeID`, but deployed clients and servers can be upgraded independently. A missing key currently flows into SQL construction and produces an HTTP 500.

## Goals / Non-Goals

**Goals:**

- Prefer the canonical `itemTypeID` parameter.
- Accept `itemtypeID` as a temporary fallback through the RSM 7 transition.
- Make the compatibility intent and removal point explicit in the source.
- Prevent missing parameters or failed queries from causing `fetch_assoc()` fatals.

**Non-Goals:**

- Change property visibility rules or the returned XML shape.
- Add compatibility aliases to unrelated endpoints.
- Remove the legacy spelling during this change.

## Decisions

- Resolve the canonical key first and only consult `itemtypeID` when it is absent. This gives deterministic precedence when both are sent.
- Keep the fallback in the endpoint instead of changing the global constant, because changing `$cstItemTypeID` would affect every legacy PHP that consumes it.
- Add the requested compatibility comment and a TODO stating that `itemtypeID` is to be removed after RSM 7.
- Validate the three numeric identifiers before querying and return the existing XML `NOK` shape for an invalid request.
- Make `getUserProperties()` tolerate query failure by returning the response structure it can safely build instead of dereferencing `false`.

## Risks / Trade-offs

- [The legacy alias remains available longer than intended] → The explicit TODO names the removal milestone.
- [Clients send both keys with different values] → The canonical `itemTypeID` value takes precedence.
- [A database error becomes an empty result] → `RSQuery()` continues recording the underlying database error, while the endpoint avoids a secondary fatal.
