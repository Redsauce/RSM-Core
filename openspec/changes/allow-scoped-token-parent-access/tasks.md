## 1. Shared scope authorization

- [x] 1.1 Audit direct, query, batch, create, and indirect item-backed scope paths in API v1/v2; identify the native item-ID filter representation and callers needing changes.
- [x] 1.2 Add exact existing-parent identity handling to shared scope authorization, validating client/type/item and rejecting other items of the parent type before dependency lookup.
- [x] 1.3 Add the exclusive parent item-ID constraint to shared query construction, combined with caller filters before counts and pagination.
- [x] 1.4 Reject scoped creation of the parent type while preserving other-type create payload handling.

## 2. API integration

- [x] 2.1 Integrate the shared constraint into API v1/v2 list/count/search and other query callers, preserving operation/property permissions and visibility.
- [x] 2.2 Verify direct item, file/picture, audit, properties, and identity lookup callers use the parent rule; repair identified bypasses.
- [x] 2.3 Check all explicitly requested batch targets before mutation; preserve all-or-nothing scope rejection and existing deletion reference cleanup.

## 3. Regression verification

- [x] 3.1 Add shared-helper coverage for own parent without self-reference, ambiguous parent dependencies, sibling parent with a matching dependency, missing parent, cross-client/type identities, invalid scope, and unchanged related-item scope.
- [x] 3.2 Verify authorized and forbidden parent edits, reads, deletes, and file/picture operations in API v1/v2, including standalone and inherited permissions.
- [x] 3.3 Verify parent list/count/search isolation, caller-filter intersection, and pagination with several parents present.
- [x] 3.4 Verify mixed mutation batches reject forbidden targets without partial changes; verify deletion retains existing reference cleanup, parent creation is denied, and related-item creation remains unchanged.
- [x] 3.5 Run focused regression checks, the existing master-token-template regression script, and PHP syntax checks for changed files; record results and any environment-dependent checks remaining.

## 4. Specification integration

- [x] 4.1 Reconcile the pending restricted-api-tokens dependency, query, create, and staff lookup requirements with this parent-type exception when preparing synchronization or archival, preserving other-type rules.
