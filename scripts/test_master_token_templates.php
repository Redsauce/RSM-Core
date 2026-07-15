<?php

// Focused utility tests for master-token behavior. The production utility is
// loaded against a small in-memory RSQuery double so these tests need no DB.

class MasterTokenFakeResult {
    private $rows;
    private $position = 0;
    public $num_rows;

    public function __construct($rows) {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        if ($this->position >= $this->num_rows) return null;
        return $this->rows[$this->position++];
    }
}

class MasterTokenFakeMysqli {
    public $inTransaction = false;
    public function real_escape_string($value) { return addslashes($value); }
    public function begin_transaction() { $this->inTransaction = true; }
    public function commit() { $this->inTransaction = false; }
    public function rollback() { $this->inTransaction = false; }
}

$mysqli = new MasterTokenFakeMysqli();
$cstRS_POST = 'RS_POST';
$GLOBALS[$cstRS_POST] = array();

$masterTokenRows = array(
    'standalone' => array('RS_ID' => 1, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 0, 'RS_CUSTOMER_ITEM_TYPE_ID' => 50, 'RS_CUSTOMER_ITEM_ID' => 60),
    'master' => array('RS_ID' => 10, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 1, 'RS_PARENT_MASTER_TOKEN' => 0),
    'child' => array('RS_ID' => 11, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 10),
    'sibling' => array('RS_ID' => 12, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 10),
    'disabled-child' => array('RS_ID' => 13, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 0, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 10),
    'dangling' => array('RS_ID' => 14, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 999),
    'parent-child' => array('RS_ID' => 20, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 1, 'RS_PARENT_MASTER_TOKEN' => 10),
    'chained' => array('RS_ID' => 21, 'RS_CLIENT_ID' => 7, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 20),
    'other-master' => array('RS_ID' => 10, 'RS_CLIENT_ID' => 8, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 1, 'RS_PARENT_MASTER_TOKEN' => 0),
    'cross-client-child' => array('RS_ID' => 22, 'RS_CLIENT_ID' => 9, 'RS_ENABLED' => 1, 'RS_MASTER_TEMPLATE' => 0, 'RS_PARENT_MASTER_TOKEN' => 10),
);

$masterTokenPermissions = array(
    '10:100:READ' => true,
    '11:101:READ' => true, // unexpected child row must never be used
    '1:102:WRITE' => true,
);

function masterTokenRowByID($id, $clientID) {
    global $masterTokenRows;
    foreach ($masterTokenRows as $token => $row) {
        if ($row['RS_ID'] == $id && $row['RS_CLIENT_ID'] == $clientID) return array($token, $row);
    }
    return null;
}

function RSQuery($query, $registerError = true) {
    global $masterTokenRows, $masterTokenPermissions;

    if (strpos($query, 'FROM rs_tokens token') !== false) {
        preg_match("/token\.RS_TOKEN = '([^']+)'/", $query, $tokenMatch);
        $token = isset($tokenMatch[1]) ? stripslashes($tokenMatch[1]) : '';
        if (!isset($masterTokenRows[$token])) return new MasterTokenFakeResult(array());
        $row = $masterTokenRows[$token];
        if (preg_match('/token\.RS_CLIENT_ID = ([0-9]+)/', $query, $clientMatch) && intval($clientMatch[1]) !== $row['RS_CLIENT_ID']) {
            return new MasterTokenFakeResult(array());
        }
        $parent = masterTokenRowByID($row['RS_PARENT_MASTER_TOKEN'], $row['RS_CLIENT_ID']);
        $parentRow = is_null($parent) ? null : $parent[1];
        return new MasterTokenFakeResult(array(array(
            'tokenID' => $row['RS_ID'], 'clientID' => $row['RS_CLIENT_ID'], 'enabled' => $row['RS_ENABLED'],
            'isMasterTemplate' => $row['RS_MASTER_TEMPLATE'], 'parentMasterTokenID' => $row['RS_PARENT_MASTER_TOKEN'],
            'customerItemTypeID' => isset($row['RS_CUSTOMER_ITEM_TYPE_ID']) ? $row['RS_CUSTOMER_ITEM_TYPE_ID'] : 0,
            'customerItemID' => isset($row['RS_CUSTOMER_ITEM_ID']) ? $row['RS_CUSTOMER_ITEM_ID'] : 0,
            'parentTokenID' => is_null($parentRow) ? null : $parentRow['RS_ID'],
            'parentEnabled' => is_null($parentRow) ? null : $parentRow['RS_ENABLED'],
            'parentIsMasterTemplate' => is_null($parentRow) ? null : $parentRow['RS_MASTER_TEMPLATE'],
            'parentParentMasterTokenID' => is_null($parentRow) ? null : $parentRow['RS_PARENT_MASTER_TOKEN'],
        )));
    }

    if (preg_match('/SELECT RS_TOKEN AS token\s+FROM rs_tokens\s+WHERE RS_ID = ([0-9]+)\s+AND RS_CLIENT_ID = ([0-9]+)/s', $query, $matches)) {
        $found = masterTokenRowByID(intval($matches[1]), intval($matches[2]));
        return new MasterTokenFakeResult(is_null($found) ? array() : array(array('token' => $found[0])));
    }

    if (preg_match('/SELECT RS_MASTER_TEMPLATE, RS_PARENT_MASTER_TOKEN\s+FROM rs_tokens\s+WHERE RS_CLIENT_ID = ([0-9]+)\s+AND RS_ID = ([0-9]+)/s', $query, $matches)) {
        $found = masterTokenRowByID(intval($matches[2]), intval($matches[1]));
        return new MasterTokenFakeResult(is_null($found) ? array() : array(array(
            'RS_MASTER_TEMPLATE' => $found[1]['RS_MASTER_TEMPLATE'],
            'RS_PARENT_MASTER_TOKEN' => $found[1]['RS_PARENT_MASTER_TOKEN'],
        )));
    }

    if (preg_match('/SELECT RS_ID FROM rs_tokens\s+WHERE RS_CLIENT_ID = ([0-9]+)\s+AND RS_ID = ([0-9]+)\s+FOR UPDATE/s', $query, $matches)) {
        $found = masterTokenRowByID(intval($matches[2]), intval($matches[1]));
        return new MasterTokenFakeResult(is_null($found) ? array() : array(array('RS_ID' => $found[1]['RS_ID'])));
    }

    if (preg_match('/SELECT 1 FROM rs_tokens\s+WHERE RS_CLIENT_ID = ([0-9]+)\s+AND RS_PARENT_MASTER_TOKEN = ([0-9]+)/s', $query, $matches)) {
        foreach ($masterTokenRows as $row) {
            if ($row['RS_CLIENT_ID'] == intval($matches[1]) && $row['RS_PARENT_MASTER_TOKEN'] == intval($matches[2])) {
                return new MasterTokenFakeResult(array(array('1' => 1)));
            }
        }
        return new MasterTokenFakeResult(array());
    }

    if (preg_match('/RS_TOKEN_ID = ([0-9]+).*RS_PROPERTY_ID= ([0-9]+).*RS_PERMISSION =\'([^\']+)\'/s', $query, $matches)) {
        $key = $matches[1] . ':' . $matches[2] . ':' . $matches[3];
        return new MasterTokenFakeResult(isset($masterTokenPermissions[$key]) ? array(array('permission' => $matches[3])) : array());
    }

    if (strpos($query, 'INSERT INTO rs_tokens') !== false || strpos($query, 'DELETE FROM rs_tokens') !== false || strpos($query, 'INSERT INTO rs_token_permissions') !== false || strpos($query, 'DELETE FROM rs_token_permissions') !== false) return true;
    return new MasterTokenFakeResult(array());
}

function ParsePID($propertyID, $clientID) { return intval($propertyID); }

require_once __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php';

function assertMasterToken($condition, $message) {
    if (!$condition) throw new Exception($message);
}

assertMasterToken(RSisTokenEnabled('standalone'), 'Enabled standalone token must authenticate');
assertMasterToken(!RSisTokenEnabled('master'), 'Master token must never authenticate');
assertMasterToken(RSisTokenEnabled('child'), 'Enabled child with enabled master must authenticate');
assertMasterToken(!RSisTokenEnabled('disabled-child'), 'Disabled child must not authenticate');
assertMasterToken(RSisTokenEnabled('sibling'), 'Disabling one child must not affect its sibling');
assertMasterToken(!RSisTokenEnabled('dangling'), 'Dangling child must fail closed');
assertMasterToken(!RSisTokenEnabled('chained'), 'Inheritance chain must fail closed');
assertMasterToken(!RSisTokenEnabled('cross-client-child'), 'Cross-client parent must fail closed');
assertMasterToken(RSgetEffectivePermissionTokenID('child') === 10, 'Child must resolve its master permission owner');
assertMasterToken(RShasTokenPermission('child', 100, 'READ'), 'Child must use a permission granted to its master');
assertMasterToken(!RShasTokenPermission('child', 101, 'READ'), 'Unexpected child permission row must be ignored');
assertMasterToken(!RScreateTokenPermission(11, 7, 100, 'READ'), 'Permission writes targeting children must be rejected');
assertMasterToken(RSisCustomerScopedToken('standalone'), 'Existing customer-scoped standalone token must keep its scope');
assertMasterToken(!RScreateToken('invalid', 7, 0, 0, true, 10), 'A master cannot also be a child');
assertMasterToken(!RScreateToken('cross-client-new', 7, 0, 0, false, 999), 'Creation must reject a missing/cross-client master');
assertMasterToken(!RScreateToken('non-master-parent', 7, 0, 0, false, 1), 'Creation must reject a standalone parent');
assertMasterToken(!RScreateToken('chain-parent', 7, 0, 0, false, 20), 'Creation must reject a parent that already has a parent');
assertMasterToken(RScreateToken('new-child', 7, 0, 0, false, 10), 'Creation must accept a valid direct master');
assertMasterToken(RScreateToken('new-standalone', 7), 'Omitted role fields must preserve standalone creation');
assertMasterToken(!RSdeleteTokenSafely('master', 7), 'Deletion must reject a referenced master');
assertMasterToken(RSdeleteTokenSafely('child', 7), 'Deletion must allow a child without modifying its master');

$masterTokenRows['master']['RS_ENABLED'] = 0;
assertMasterToken(!RSisTokenEnabled('child'), 'Disabled master must block its child');
assertMasterToken(!RSisTokenEnabled('sibling'), 'Disabled master must block all siblings');
$masterTokenRows['master']['RS_ENABLED'] = 1;
assertMasterToken(RSisTokenEnabled('child') && RSisTokenEnabled('sibling'), 'Re-enabled master must restore enabled children');
assertMasterToken(!RSisTokenEnabled('disabled-child'), 'Re-enabled master must not restore a disabled child');

$repoRoot = dirname(__DIR__);
$newTokenEndpoint = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/api/classLbxTokens_newToken.php');
$editTokenEndpoint = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/api/classLbxTokens_editToken.php');
$securityCheck = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/utilities/RSsecurityCheck.php');
$tokenUtilities = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php');
$permissionReadEndpoint = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/api/wndAPI_getPermissions.php');
$schema = file_get_contents($repoRoot . '/Database/schema.sql');
$migration = file_get_contents($repoRoot . '/Server/htdocs/AppController/commands_RSM/updater/server/phpUpdate_From_v6.9.0.3.164_to_v7.0.0.3.165/update_post.sql');

assertMasterToken(strpos($newTokenEndpoint, "['isMasterTemplate']") !== false, 'Token creation API must accept isMasterTemplate');
assertMasterToken(strpos($newTokenEndpoint, "['parentMasterTokenID']") !== false, 'Token creation API must accept parentMasterTokenID');
assertMasterToken(strpos($editTokenEndpoint, 'TOKEN ROLE AND MASTER RELATIONSHIP ARE IMMUTABLE') !== false, 'Token edit API must reject immutable role fields');
assertMasterToken(strpos($tokenUtilities, "RS_MASTER_TEMPLATE AS 'isMasterTemplate'") !== false, 'Token listing must expose master status');
assertMasterToken(strpos($tokenUtilities, "RS_PARENT_MASTER_TOKEN AS 'parentMasterTokenID'") !== false, 'Token listing must expose parent ID');
assertMasterToken(strpos($permissionReadEndpoint, 'RSgetEffectivePermissionTokenID') !== false, 'Permission reads must resolve inherited permissions');
assertMasterToken(substr_count($securityCheck, 'RSisTokenEnabled(') >= 3, 'POST and GET API authentication paths must use centralized token validation');
assertMasterToken(strpos($schema, '`RS_PARENT_MASTER_TOKEN`') !== false, 'Canonical schema must define RS_PARENT_MASTER_TOKEN');
assertMasterToken(strpos($migration, 'ADD IF NOT EXISTS RS_PARENT_MASTER_TOKEN') !== false, 'Updater must add the canonical parent column idempotently');
assertMasterToken(strpos($migration, 'ADD INDEX IF NOT EXISTS RS_PARENT_MASTER_TOKEN') !== false, 'Updater must add the parent lookup index idempotently');

$apiIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $repoRoot . '/Server/htdocs/AppController/commands_RSM/api',
    FilesystemIterator::SKIP_DOTS
));
foreach ($apiIterator as $apiFile) {
    if ($apiFile->getExtension() !== 'php') continue;
    $source = file_get_contents($apiFile->getPathname());
    if (strpos($source, 'setAuthorizationTokenOnGlobals()') !== false) {
        assertMasterToken(strpos($source, 'RSdatabase.php') !== false, $apiFile->getFilename() . ' must pass v2 authorization through RSsecurityCheck');
    }
}

echo "master-token-templates utility tests passed\n";
