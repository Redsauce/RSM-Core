## Verification

- `php scripts/test_master_token_templates.php`
  - Covers standalone, master, valid child, disabled child, disabled master, siblings, dangling/cross-client/chained relationships, effective permissions, ignored child permission rows, rejected child permission writes, valid/invalid creation, protected master deletion, child deletion, and customer-scope regression behavior.
  - Verifies the token-management request/response field contract, immutable edit behavior, inherited permission reads, canonical schema/migration names, and that every API v2 endpoint using the Authorization header loads the centralized security check.
- PHP syntax validation was run for every `.php` file under `Server/htdocs/AppController/commands_RSM`.
- `openspec validate master-token-templates --strict` validates the change artifacts.

## Response behavior

- Existing standalone token responses remain unchanged apart from the additive `isMasterTemplate` and `parentMasterTokenID` fields in token management results.
- Invalid master/child creation returns the existing HTTP 400 error style from `classLbxTokens_newToken.php`.
- Attempts to change immutable role fields, mutate child permissions, or delete a referenced master return `result = NOK` with a descriptive message.
- API v1/v2 requests authenticated with a master, a disabled child, a child of a disabled master, or an invalid child relationship use the existing centralized `ACCESS DENIED` response style.
