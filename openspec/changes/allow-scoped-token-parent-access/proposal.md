## Why

Customer-scoped tokens currently require a direct customer dependency even when accessing the customer item that defines their own scope. This prevents users from editing their own parent item when it has no self-reference, despite having the necessary permissions.

## What Changes

- Recognize the existing scope-defining parent by its client, item type, and item ID without requiring a self-reference.
- For the configured parent item type, allow only that exact parent; reject every other parent even if it refers to the allowed parent.
- Apply this rule consistently to direct access, queries, batches, and item-backed operations in API v1 and v2.
- Keep operation/property permissions, visibility, authentication, and inherited master-template permissions required.
- Preserve direct dependency rules for related items of other types; the exception does not authorize creating other parents or accessing unrelated items.

## Capabilities

### New Capabilities
- `scoped-token-parent-access`: Defines exact-identity access to the scope-defining parent and isolation from other parents across API operations.

### Modified Capabilities

None in `openspec/specs/`, which does not yet exist. This capability refines the pending `restricted-api-tokens` change: its dependency-only rules must be reconciled with this parent-type exception when syncing or archiving both changes.

## Impact

- Shared scope helpers and query construction in `Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php`.
- API v1/v2 direct, list/count/search, batch, file/picture, lookup, and other item-backed callers of those helpers.
- Focused regression coverage for parent access, permissions, query isolation, and unchanged related-item behavior.
- No database schema, token configuration, or permission-model changes.
