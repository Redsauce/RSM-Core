## Context

RSM stores tokens in `rs_tokens` and their per-property CREATE, READ, WRITE, and DELETE grants in `rs_token_permissions` (called token properties in parts of the product vocabulary). Token-management handlers create, list, edit, enable, disable, delete, display permissions, and mutate permissions. API v1 and v2 authorization ultimately uses helpers in `RSMtokensManagement.php`, but some entry points resolve a client before checking a permission.

`RS_MASTER_TEMPLATE` has already been added to the active updater SQL with a false default. The repository does not yet contain `RS_PARENT_MASTER_TOKEN`, and `Database/schema.sql` does not yet describe either field. Existing rows must remain standalone tokens.

## Goals / Non-Goals

**Goals:**
- Represent non-authenticating master templates and direct child-to-master relationships.
- Make a child's effective property permissions come entirely from its master.
- Keep existing standalone token behavior backward compatible.
- Centralize authentication rejection and effective-permission resolution so all API surfaces behave consistently.
- Make creation, listing, permission management, enabled-state, authentication, and deletion rules explicit and fail closed.

**Non-Goals:**
- Multi-level or multiple inheritance.
- Combining child-specific permissions with master permissions.
- Copying master permission rows into every child.
- Changing customer-scope inheritance; a child keeps and enforces its own customer-scope fields.
- Redesigning token strings or the existing permission model.
- Converting standalone, master, or child tokens after creation, including assigning, clearing, or changing a parent.

## Decisions

### Set immutable roles during creation

Token creation accepts optional `isMasterTemplate`, `parentMasterToken`, and backward-compatible `parentMasterTokenID` fields. `isMasterTemplate = true` creates a master and requires no parent; a valid `parentMasterToken` or positive `parentMasterTokenID` creates a child and requires `isMasterTemplate` to be false or omitted; omitting both parent fields creates a standalone token. The preferred API field is `parentMasterToken`: the server resolves it to the client-local numeric ID before persistence so callers do not need database identifiers. Supplying it together with a positive `parentMasterTokenID` is rejected as ambiguous.

Token creation and listing responses return the token's client-local `ID` together with its role and parent fields. This lets management clients match `parentMasterTokenID` to the corresponding master row without using the token credential as a relational key. The role and parent are immutable after creation: edit handlers do not accept changes to these fields and MUST reject them if supplied. Administrators who need a different role or parent must create a new token and retire the old one.

Alternative considered: one `masterToken` parameter with overloaded boolean/ID meaning. Rejected because it cannot clearly distinguish “this is a master” from “this is a child of master N”.

### Store a direct, client-local parent ID

Add `RS_PARENT_MASTER_TOKEN` as an unsigned integer with `0` as “no parent”. Its value refers to `rs_tokens.RS_ID` within the same `RS_CLIENT_ID`; token IDs are client-local, so every lookup uses the pair `(RS_CLIENT_ID, RS_ID)`. A valid row is exactly one of:

- standalone: `RS_MASTER_TEMPLATE = FALSE`, parent `0`;
- master: `RS_MASTER_TEMPLATE = TRUE`, parent `0`;
- child: `RS_MASTER_TEMPLATE = FALSE`, parent points to a master in the same client.

Database constraints in the current schema cannot express all cross-row rules reliably, so shared creation helpers enforce them transactionally. Add an index on `(RS_CLIENT_ID, RS_PARENT_MASTER_TOKEN)` for child lookup and deletion validation.

Alternative considered: store the parent's token string. Rejected because the numeric client-local ID remains the intended relationship key. The management API may accept that string as an authorized lookup value, but resolves it immediately and persists only the numeric ID.

### Separate management lookup from authentication lookup

Master templates must remain addressable by authenticated administration handlers but must never authenticate an API call. Introduce a raw management metadata lookup that can retrieve any token belonging to a client, and an authentication lookup that requires an existing, enabled, non-master token with valid relationship metadata. When the authenticating token is a child, the lookup also requires its parent master to be enabled.

Existing API paths that use `RSclientFromToken`, `RSisTokenEnabled`, `getRStoken`, or equivalent bootstrap logic must pass through the authentication rule before client data is accessed. Administrative token handlers use the management lookup after their normal user/login authorization. A master presented as an API credential fails with the endpoint's normal unauthorized/forbidden response and never falls back to user-visible-property authorization.

Alternative considered: enforce the rule only in permission checks. Rejected because endpoints can resolve client context or perform non-property operations before asking for a property permission.

### Resolve one effective permission owner

Add a shared resolver that returns the effective permission token ID:

- standalone token -> its own `RS_ID`;
- child token -> `RS_PARENT_MASTER_TOKEN`, after verifying the parent exists, belongs to the same client, is a master, and is enabled;
- master in an authentication context or any invalid relationship -> no effective permission owner and fail closed.

All runtime permission helpers query `rs_token_permissions` with this effective ID. Permission-display handlers show the master's effective grants when a child is selected, but responses identify the child and expose its parent so the UI can make the inherited/read-only state clear.

Permission mutation handlers accept masters and standalone tokens as owners. They reject mutations targeted at children; administrators change the parent master instead. This prevents hidden child overrides.

Alternative considered: join child and master rows and merge grants. Rejected because the requested model says children have no associated permission rows and because merge/revoke precedence would be ambiguous.

### Keep roles immutable and deletion safe

Creating a child validates its parent before inserting the row. A newly created child has no permission rows. No edit path promotes a standalone token, demotes a master, assigns or clears a parent, or reassigns a child to a different master.

Deleting a master while children reference it is rejected. Deleting a child removes the token and defensively removes any unexpected child permission rows. Deleting a standalone token or an unreferenced master retains the existing permission cleanup behavior.

Alternative considered: support in-place role conversion or cascade-detach children. Rejected for this iteration because it adds destructive migration semantics; operators can create replacement tokens explicitly.

### Keep customer scope on the authenticating child

Only property permissions are inherited. Customer item type, customer item ID, alias, token string, enabled state, and other token metadata remain specific to the child. A child must itself be enabled and customer-scope-valid, and its parent master must also be enabled. Disabling an individual child blocks only that credential and does not modify or disable its master or siblings. Disabling or unpublishing the master acts as an immediate kill switch for every child without modifying the child rows; enabling the same master restores only those children that are themselves enabled and otherwise eligible.

## Risks / Trade-offs

- [A missed API entry point could accept a master] -> Centralize the non-master rule and inventory every token-authenticated PHP entry point, including non-property operations and binary endpoints.
- [Invalid legacy/manual relationships could grant access] -> Validate parent, client, and master status on every effective-permission resolution and fail closed.
- [Concurrent child creation and master deletion could create a dangling child] -> Validate and lock the referenced master during creation/deletion transactions.
- [Many children add lookup cost] -> Use the composite parent index and resolve token plus parent metadata in one query where practical.

## Migration Plan

1. Add both columns to `Database/schema.sql`; retain the guarded updater addition for `RS_MASTER_TEMPLATE` and add guarded `RS_PARENT_MASTER_TOKEN` plus the composite index.
2. Deploy shared metadata, validation, authentication, and effective-permission helpers before exposing the new management fields.
3. Update management and permission endpoints, then audit all API authentication entry points.
4. Backfill nothing: existing `RS_MASTER_TEMPLATE = FALSE` and parent `0` rows remain standalone and retain their permissions.
5. Add masters and associate children only after the new code is deployed.

Rollback requires retiring child tokens created under this model before reverting authorization code. The added columns may remain harmlessly in place.
