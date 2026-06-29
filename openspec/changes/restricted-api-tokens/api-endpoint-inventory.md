## API Endpoint Inventory

### Item-backed endpoints with customer-scope enforcement

- `api/api_createItem.php`
- `api/api_deleteItem.php`
- `api/api_deleteItems.php`
- `api/api_getAuditTrail.php`
- `api/api_getFile.php`
- `api/api_getItem.php`
- `api/api_getItemFromProperty.php`
- `api/api_getItems.php`
- `api/api_getItemsCount.php`
- `api/api_getPendingActions.php`
- `api/api_getPicture.php`
- `api/api_getProperties.php`
- `api/api_getStaffID.php`
- `api/api_getUserID.php`
- `api/api_updateItem.php`
- `api/api_updateItems.php`
- `api/rss.php`
- `api/v2/audit/get.php`
- `api/v2/file/get.php`
- `api/v2/items/create.php`
- `api/v2/items/delete.php`
- `api/v2/items/get.php`
- `api/v2/items/getCount.php`
- `api/v2/items/getItemFromProperty.php`
- `api/v2/items/update.php`
- `api/v2/picture/get.php`
- `api/v2/properties/get.php`
- `api/v2/staff/get.php`
- `api/v2/user/get.php`

### Token-management endpoints

- `api/classLbxTokens_newToken.php`
- `api/classLbxTokens_getTokens.php`
- `api/classLbxTokens_enableToken.php`
- `api/classLbxTokens_disableToken.php`
- `api/classLbxTokens_deleteToken.php`
- `api/wndAPI_getPermissions.php`
- `api/wndAPI_getTokenItemTypePermissions.php`
- `api/wndAPI_removePermission.php`
- `api/wndAPI_setPermission.php`

### Metadata endpoints

- `api/v2/items/getTypes.php`: returns item type/property metadata; no item values are returned.

### No-code-change exemptions

- `api/api_headers.php`: shared response header include; no direct request processing.
- `api/public/timezones.php`: public static timezone data; no token or item data.

### Trigger endpoints

- `api/api.php`: queues configured URL trigger actions by trigger name for the token client. The request does not target a specific item, so customer-scoped tokens are allowed when their token scope metadata is valid; partial customer scope values still fail closed.
