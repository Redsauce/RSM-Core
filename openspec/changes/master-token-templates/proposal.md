## Why

Administrators currently have to duplicate and maintain the same property permissions across potentially hundreds of customer tokens. Master token templates provide a single non-authenticating permission source that child tokens can inherit, reducing repetitive administration and preventing permission drift.

## What Changes

- Add optional `isMasterTemplate`, `parentMasterToken`, and backward-compatible `parentMasterTokenID` fields to token creation, backed by `rs_tokens.RS_MASTER_TEMPLATE` and `rs_tokens.RS_PARENT_MASTER_TOKEN`; omission creates an ordinary standalone token.
- Treat a token's standalone, master, or child role as immutable after creation; this change does not support converting, associating, detaching, or reassigning existing tokens.
- Return each token's client-local `ID`, master-template status, and parent information from token creation and listing calls.
- Store property permissions only for standard tokens and master templates. Child tokens resolve all property permissions from their referenced master template and must not retain rows in `rs_token_permissions`.
- Reject API authentication with a token whose `RS_MASTER_TEMPLATE` value is true, regardless of its configured permissions.
- Require both the child token and its parent master template to be enabled; disabling or unpublishing a master immediately prevents all of its children from authenticating until the master is enabled again.
- Keep each child's enabled state independent; disabling a child blocks only that child and does not affect its master or sibling tokens.
- Validate that a child and its master belong to the same client, that the parent is a master template, and that master templates cannot themselves be children.
- Prevent deletion of referenced master templates and keep existing tokens as ordinary standalone tokens by default.

## Capabilities

### New Capabilities
- `master-token-templates`: Defines immutable master/child roles, inherited permission resolution, enabled-state behavior, and API authentication rules.

### Modified Capabilities

## Impact

- Database schema and v6.9.0.3.164 to v7.0.0.3.165 updater for `rs_tokens.RS_MASTER_TEMPLATE` and `rs_tokens.RS_PARENT_MASTER_TOKEN`.
- Token utilities in `Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php`.
- Minimal token-management support for creating/listing the immutable token role and administering master permissions.
- API v1 and API v2 authorization plus shared authentication/token validation used by trigger, file, picture, feed, and any other token-authenticated entry points.
- Tests or API verification fixtures for standalone, master-template, child, invalid-parent, deletion, enabled-state, inherited-permission, and authentication cases.
