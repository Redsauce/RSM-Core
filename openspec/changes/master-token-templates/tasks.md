## 1. Database Schema and Migration

- [x] 1.1 Add `RS_MASTER_TEMPLATE` and `RS_PARENT_MASTER_TOKEN` with false/zero defaults to the `rs_tokens` definition in `Database/schema.sql`.
- [x] 1.2 Extend the v6.9.0.3.164 to v7.0.0.3.165 `update_post.sql` migration with a guarded `RS_PARENT_MASTER_TOKEN` addition and make the existing master column migration consistent with the canonical schema.
- [x] 1.3 Add an idempotent index for `(RS_CLIENT_ID, RS_PARENT_MASTER_TOKEN)` and verify the updater can be rerun safely.

## 2. Token Metadata and Relationship Utilities

- [x] 2.1 Add a shared token metadata lookup that returns token ID, client ID, enabled state, master status, parent ID, customer scope, and relationship validity without treating a master as an API credential.
- [x] 2.2 Add validation for the three legal roles (standalone, master, child), including same-client parent lookup, parent master status, and prohibition of inheritance chains.
- [x] 2.3 Add a shared effective-permission-owner resolver that returns the token's own ID for standalone tokens, its direct master's ID for children, and failure for invalid relationships or authenticating masters.
- [x] 2.4 Add child-count/reference helpers used to prevent deletion of referenced masters.
- [x] 2.5 Implement transactional creation validation for immutable standalone, master, and child roles, ensuring new children receive no permission rows.

## 3. Token Management API

- [x] 3.1 Update `RScreateToken` and `classLbxTokens_newToken.php` to accept and validate optional `isMasterTemplate` and `parentMasterTokenID` fields while preserving existing caller defaults.
- [x] 3.2 Ensure `RSeditToken` and `classLbxTokens_editToken.php` cannot modify `isMasterTemplate` or `parentMasterTokenID` and reject any request attempting role conversion or parent reassignment.
- [x] 3.3 Update `RStokensFromClient` and `classLbxTokens_getTokens.php` responses to return `isMasterTemplate` and `parentMasterTokenID`.
- [x] 3.4 Update enable/disable behavior so changing a master does not rewrite child rows and gates every child, while disabling one child affects only that child and leaves its master and siblings unchanged.
- [x] 3.5 Update deletion handling to reject referenced-master deletion and to clean up any unexpected permission rows when deleting a child.
- [x] 3.6 Return clear `NOK` responses for invalid parents, cross-client references, master-as-child creation, referenced-master deletion, and attempts to change an existing token's role or parent.

## 4. Permission Administration and Resolution

- [x] 4.1 Update `RShasTokenPermission`, plural/convenience permission helpers, and translated-identifier recursion to use the shared effective permission owner consistently.
- [x] 4.2 Update `wndAPI_getPermissions.php`, `wndAPI_getTokenItemTypePermissions.php`, and related permission reads so selecting a child displays its master's effective permissions and inherited parent metadata.
- [x] 4.3 Reject permission create/delete mutations for child tokens while continuing to permit mutations for standalone tokens and master templates.
- [x] 4.4 Verify a master permission addition/removal affects all children immediately and that unexpected child permission rows are never merged.

## 5. Authentication Enforcement

- [x] 5.1 Introduce or update the shared API authentication lookup so master templates are rejected as credentials and a child can authenticate only when both the child and its parent master are enabled.
- [x] 5.2 Separate administrative raw token lookup from API credential lookup so token-management endpoints can still address masters after administrator authentication.
- [x] 5.3 Audit every token-authenticated endpoint under `Server/htdocs/AppController/commands_RSM/api/`, including v1, v2, trigger, feed, pending-action, file, picture, and helper paths, and route each through master rejection.
- [x] 5.4 Verify invalid/dangling child relationships fail closed on every authenticated API path.
- [x] 5.5 Verify child-specific customer scope, enabled state, alias, and other metadata remain independent, while disabling or re-enabling a master immediately blocks or restores all otherwise valid children without modifying them.

## 6. Verification

- [x] 6.1 Add database/utility tests for standalone, master, valid child, cross-client parent, non-master parent, chain, dangling parent, immutable-role rejection, and deletion cases.
- [x] 6.2 Add token-management API tests for omitted defaults, explicit true/false and positive/zero fields, list responses, inherited permission reads, and rejected child permission writes.
- [x] 6.3 Add API v1 and v2 authentication tests proving masters cannot authenticate, valid children use master permissions, a disabled master blocks all children, re-enabling it restores only enabled valid children, and disabling one child does not affect its master or siblings.
- [x] 6.4 Add regression tests proving existing standalone and customer-scoped tokens preserve their current authentication, permissions, and scope behavior.
- [x] 6.5 Run PHP syntax checks and the relevant automated/API verification suite, then document any endpoint-specific response differences.
