<?php
// SEC-02: run the production writers and API endpoints against SQLite prepared
// statements. This does not exercise the MySQL wire protocol or row locking.
if (!class_exists('SQLite3')) throw new RuntimeException('Enable sqlite3 to run these tests.');
function binaryAssert($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function binaryExtractFunction($source, $name) {
    $start = strpos($source, 'function ' . $name . '(');
    binaryAssert($start !== false, 'Missing production function: ' . $name);
    $depth = 0; $started = false; $result = '';
    foreach (token_get_all('<?php ' . substr($source, $start)) as $token) {
        if (is_array($token) && $token[0] === T_OPEN_TAG) continue;
        $result .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $started = true; }
        if ($token === '}') $depth--;
        if ($started && $depth === 0) return $result;
    }
    throw new RuntimeException('Unclosed production function: ' . $name);
}
// Mixed deployment regression: real property definitions from the pre-hardening
// revision, combined with the current writer. No production connection is used.
function binaryBaselineSource($revision, $path) {
    $process = proc_open(array('git', 'show', $revision . ':' . $path),
        array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, dirname(__DIR__));
    if (!is_resource($process)) throw new RuntimeException('Unable to read baseline');
    $source = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    binaryAssert(proc_close($process) === 0, 'Unable to read baseline: ' . $error);
    return $source;
}
class BinaryResponse extends RuntimeException {
    public $body;
    function __construct($status, $body) { parent::__construct('', $status); $this->body = $body; }
}
class BinaryRows {
    public $num_rows;
    private $rows = array();
    function __construct($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $this->rows[] = $row;
        $this->num_rows = count($this->rows);
    }
    function fetch_assoc() { return array_shift($this->rows); }
}
class BinaryStatement {
    private $sql, $types, $parameters = array(), $blobs = array();
    private $resultRows = array(), $resultBindings = array();
    public $closed = false;
    function __construct($sql) { $this->sql = $sql; }
    function bind_param($types, &...$parameters) {
        binaryAssert(strlen($types) === count($parameters), 'Binding count mismatch');
        binaryAssert(substr_count($this->sql, '?') === count($parameters), 'Placeholder count mismatch');
        $this->types = $types;
        foreach ($parameters as $index => &$parameter) $this->parameters[$index] = &$parameter;
        return true;
    }
    function send_long_data($index, $chunk) {
        if ($GLOBALS['binaryFail'] === 'send') return false;
        binaryAssert($this->types[$index] === 'b', 'Chunk sent to a non-binary parameter');
        binaryAssert(strlen($chunk) <= 65536, 'Chunk exceeds streaming bound');
        $this->blobs[$index] = ($this->blobs[$index] ?? '') . $chunk;
        $GLOBALS['binaryChunkCount']++;
        return true;
    }
    function execute() {
        $GLOBALS['binaryExecuted']++;
        if ($GLOBALS['binaryFailAfter'] > 0 && $GLOBALS['binaryExecuted'] === $GLOBALS['binaryFailAfter']) return false;
        if ($GLOBALS['binaryFail'] !== '' && strpos($this->sql, $GLOBALS['binaryFail']) !== false) return false;
        $sql = str_replace(array(' FOR UPDATE', 'GREATEST('), array('', 'MAX('), $this->sql);
        $statement = $GLOBALS['binaryDb']->prepare($sql);
        foreach ($this->parameters as $index => $parameter) {
            $value = $this->types[$index] === 'b' ? ($this->blobs[$index] ?? null) : $parameter;
            $type = $this->types[$index] === 'b' ? SQLITE3_BLOB : ($this->types[$index] === 'i' ? SQLITE3_INTEGER : SQLITE3_TEXT);
            $statement->bindValue($index + 1, $value, $type);
        }
        $result = $statement->execute();
        if ($result) {
            if (strpos($sql, 'SELECT ') === 0) {
                while ($row = $result->fetchArray(SQLITE3_NUM)) $this->resultRows[] = $row;
            }
            $result->finalize();
        }
        $statement->close();
        return $result !== false;
    }
    function close() { $this->closed = true; }
    function bind_result(&...$values) {
        foreach ($values as $index => &$value) $this->resultBindings[$index] = &$value;
        return true;
    }
    function fetch() {
        $row = array_shift($this->resultRows);
        if ($row === null) return null;
        foreach ($row as $index => $value) $this->resultBindings[$index] = $value;
        return true;
    }
}
class BinaryConnection {
    public $statements = array(), $commits = 0, $rollbacks = 0;
    function prepare($sql) {
        $statement = new BinaryStatement($sql);
        $this->statements[] = $statement;
        return $statement;
    }
    function begin_transaction() {
        if ($GLOBALS['binaryFail'] === 'begin') return false;
        return $GLOBALS['binaryDb']->exec('BEGIN');
    }
    function commit() {
        if ($GLOBALS['binaryFail'] === 'commit') return false;
        $this->commits++;
        return $GLOBALS['binaryDb']->exec('COMMIT');
    }
    function rollback() { $this->rollbacks++; return $GLOBALS['binaryDb']->exec('ROLLBACK'); }
}
function RSQuery($sql, $registerError = true) {
    if ($GLOBALS['binaryFail'] !== '' && strpos($sql, $GLOBALS['binaryFail']) !== false) return false;
    $sql = str_replace(' FOR UPDATE', '', $sql);
    if (strpos($sql, 'SELECT ') === 0) return new BinaryRows($GLOBALS['binaryDb']->query($sql));
    return $GLOBALS['binaryDb']->exec($sql);
}
function getNextItemTypeIdentification($type, $client) {
    return 1 + (int)$GLOBALS['binaryDb']->querySingle("SELECT RS_LAST_ITEM_ID FROM rs_item_types WHERE RS_CLIENT_ID=$client AND RS_ITEMTYPE_ID=$type");
}
function getNextIdentification($table, $column, $client, $extra = null) {
    if ($table === 'rs_item_types') return 1 + (int)$GLOBALS['binaryDb']->querySingle("SELECT MAX(RS_ITEMTYPE_ID) FROM rs_item_types WHERE RS_CLIENT_ID=$client");
    $type = (int)$extra['RS_ITEMTYPE_ID'];
    return 1 + (int)$GLOBALS['binaryDb']->querySingle("SELECT MAX(RS_ITEM_ID) FROM rs_items WHERE RS_CLIENT_ID=$client AND RS_ITEMTYPE_ID=$type");
}
function getGenericNext($table, $column, $conditions) {
    $client = (int)$conditions['RS_CLIENT_ID'];
    return 1 + (int)$GLOBALS['binaryDb']->querySingle("SELECT MAX($column) FROM $table WHERE RS_CLIENT_ID=$client");
}
function getUserVisiblePropertiesIDs($type, $client, $user) { return array_diff($GLOBALS['binaryProperties'], $GLOBALS['binaryDenied']); }
function RSReturnError($message, $code) { throw new BinaryResponse(400, array('result' => 'NOK', 'description' => $message, 'code' => $code)); }
function getPropertyType($id, $client) { return $GLOBALS['binaryTypes'][(int)$id] ?? ''; }
function getClientItemTypeProperties($type, $client, $exclude = 0, $failOnError = false) {
    if ($GLOBALS['binaryFail'] === 'metadata') throw new RuntimeException('Metadata unavailable');
    return array_map(function ($id) { return array('id' => $id, 'type' => getPropertyType($id, 7)); }, $GLOBALS['binaryProperties']);
}
function getClientPropertyDefaultValue($id, $client) { return $GLOBALS['binaryDefaults'][$id] ?? ''; }
function getItemTypeIDFromProperties($ids, $client) { return count($ids) ? 50 : 0; }
function checkForAuditTrail($id, $client) { return array('auditTrail' => in_array((int)$id, $GLOBALS['binaryAudited'], true) ? 1 : 0); }
function parsePID($id, $client) { return (int)$id; }
function handleApiCorsPreflight($methods) { }
function setAuthorizationTokenOnGlobals() { }
function checkCorrectRequestMethod($method) { }
function getRequestBody() { return $GLOBALS['binaryBody']; }
function getRStoken() { return "token'quoted"; }
function RSclientFromToken($RStoken) { return 7; }
function getRSuserID() { return 3; }
function RShasTokenPermission($token, $id, $permission) { return !in_array((int)$id, $GLOBALS['binaryDenied'], true); }
function isPropertyVisible($user, $id, $client) { return false; }
function RSapplyTokenCustomerScopeToCreatePayload($token, $client, $type, $values) { return $values; }
function RSitemMatchesTokenCustomerScope($token, $client, $type, $item) { return !$GLOBALS['binaryScopeDenied']; }
function verifyItemExists($item, $type, $client) { return (bool)$GLOBALS['binaryDb']->querySingle("SELECT COUNT(*) FROM rs_items WHERE RS_CLIENT_ID=$client AND RS_ITEMTYPE_ID=$type AND RS_ITEM_ID=$item"); }
function setPropertyValueByID($id, $type, $item, $client, $value, $propertyType) { $GLOBALS['binaryTextUpdates']++; return 0; }
function deleteMediaFile($client, $item, $property) { }
function returnJsonMessage($code, $message) { throw new BinaryResponse($code, $message); }
function returnJsonResponse($json) { throw new BinaryResponse(200, json_decode($json, true)); }
function RSReturnArrayResults($body, $compress = false) { throw new BinaryResponse(200, $body); }
function dieWithError($status, $message = null) { throw new BinaryResponse($status, $message); }

$root = dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/';
set_include_path($root . 'utilities' . PATH_SEPARATOR . get_include_path());
require $root . 'utilities/RSMbinaryProperties.php';
if (in_array('--legacy-properties', $argv, true)) {
    eval(substr(binaryBaselineSource('c284da4', 'Server/htdocs/AppController/commands_RSM/utilities/RSMpropertiesManagement.php'), 5));
} else {
    require $root . 'utilities/RSMpropertiesManagement.php';
}
require $root . 'utilities/RSMbinaryUploads.php';
require $root . 'utilities/RSMverifyBody.php';
$tools = file_get_contents($root . 'utilities/RStools.php');
eval(binaryExtractFunction($tools, 'replaceUtf8Characters'));
eval(binaryExtractFunction($tools, 'isBase64'));
$manager = file_get_contents($root . 'utilities/RSMitemsManagement.php');
if (in_array('--legacy-manager', $argv, true)) {
    binaryAssert(in_array('--legacy-properties', $argv, true), 'Legacy manager test requires legacy properties');
    $manager = binaryBaselineSource('c284da4', 'Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
}
$creationFunctions = array('createEmptyItem', 'createItem', '_dbInsertItemPropertyValue', 'setDataPropertyValueByID', 'deleteItemPropertyValue', 'RSlockItemTypeForDuplication');
if (!in_array('--legacy-manager', $argv, true)) {
    $creationFunctions[] = 'RScreateItemInTransaction';
    $creationFunctions[] = 'RSallocateNextIntegerPropertyValue';
}
foreach ($creationFunctions as $name) {
    eval(binaryExtractFunction($manager, $name));
}
$binaryEndpoints = array();
foreach (array('create', 'update') as $name) {
    $source = file_get_contents($root . 'api/v2/items/' . $name . '.php');
    $source = preg_replace('/^require_once .*;\R/m', '', substr($source, 5));
    $verify = 'verifyBinary' . ucfirst($name) . 'BodyContent';
    eval(binaryExtractFunction(str_replace('verifyBodyContent', $verify, $source), $verify));
    $binaryEndpoints[$name] = str_replace('verifyBodyContent', $verify, substr($source, 0, strpos($source, '// Verify if body')));
}
$binaryV1Endpoints = array();
foreach (array('api_createItem', 'api_updateItem', 'api_updateItems') as $name) {
    $source = file_get_contents($root . 'api/' . $name . '.php');
    $binaryV1Endpoints[$name] = preg_replace('/^require_once .*;\R/m', '', substr($source, 5));
}
$binaryAdminEndpoints = array();
foreach (array('itemsManager/classLbxItemtypesList_createItemtype.php', 'itemsManager/classLbxItemtypesList_updateItemtype.php',
    'Globals/classLbxGlobals_update.php', 'itemsManager/classWndImportCSV_import.php', 'shared/classDataManager_editItems.php', 'shared/classDataManager_createItems.php',
    'genericTreeListBox/classGenericTreeListBox_createItem.php') as $path) {
    $source = file_get_contents($root . $path);
    $binaryAdminEndpoints[$path] = preg_replace('/^require_once .*;\R/m', '', substr($source, 5));
}
function binaryReset() {
    global $binaryDb, $mysqli, $RSallowDebug, $RSmaxBinaryPropertyBytes, $RSuserID, $RStoken, $RSimageCache, $RSfileCache;
    $binaryDb = new SQLite3(':memory:');
    $binaryDb->enableExceptions(true);
    $mysqli = new BinaryConnection();
    $RSallowDebug = true; $RSmaxBinaryPropertyBytes = 32 * 1024 * 1024;
    $RSuserID = 3; $RStoken = "token'quoted";
    $RSimageCache = $RSfileCache = sys_get_temp_dir() . '/nonexistent-rsm-binary-test-cache';
    $GLOBALS['binaryFail'] = ''; $GLOBALS['binaryChunkCount'] = 0; $GLOBALS['binaryAudited'] = array();
    $GLOBALS['binaryExecuted'] = 0; $GLOBALS['binaryFailAfter'] = 0;
    $GLOBALS['binaryTypes'] = array(100 => 'text', 101 => 'file', 102 => 'image');
    $GLOBALS['binaryProperties'] = array(100, 101, 102); $GLOBALS['binaryDefaults'] = array();
    $GLOBALS['binaryDenied'] = array(); $GLOBALS['binaryScopeDenied'] = false; $GLOBALS['binaryTextUpdates'] = 0;
    $GLOBALS['RSMcreatedItemIDs'] = array(); $GLOBALS['RSMupdatedItemIDs'] = array();
    $binaryDb->exec("CREATE TABLE rs_item_types (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_LAST_ITEM_ID DEFAULT 0, RS_MAIN_PROPERTY_ID DEFAULT 0, RS_NAME DEFAULT '', RS_ICON BLOB DEFAULT '', RS_ORDER DEFAULT 0);
        INSERT INTO rs_item_types (RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_LAST_ITEM_ID) VALUES (7,50,1);
        CREATE TABLE rs_items (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, PRIMARY KEY(RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID)); INSERT INTO rs_items VALUES (7,50,1);");
    $binaryDb->exec('CREATE TABLE rs_globals (RS_CLIENT_ID, RS_NAME, RS_VALUE BLOB, RS_IMAGE, PRIMARY KEY(RS_CLIENT_ID,RS_NAME))');
    foreach (array('rs_property_files', 'rs_property_images') as $table) {
        $binaryDb->exec("CREATE TABLE $table (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_NAME, RS_SIZE, RS_DATA BLOB, PRIMARY KEY(RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ITEM_ID,RS_PROPERTY_ID))");
    }
    $binaryDb->exec('CREATE TABLE rs_property_text (RS_ITEMTYPE_ID,RS_ITEM_ID,RS_DATA,RS_PROPERTY_ID,RS_CLIENT_ID)');
    foreach (array('rs_audit_trail_property_files', 'rs_audit_trail_property_images') as $table) {
        $binaryDb->exec("CREATE TABLE $table (RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ITEM_ID,RS_PROPERTY_ID,RS_USER_ID,RS_TOKEN,RS_CHANGED_DATE,RS_INITIAL_NAME,RS_INITIAL_SIZE,RS_INITIAL_VALUE BLOB,RS_FINAL_NAME,RS_FINAL_SIZE,RS_FINAL_VALUE BLOB)");
    }
    $binaryDb->exec('CREATE TABLE rs_audit_trail_property_text (RS_ITEMTYPE_ID,RS_ITEM_ID,RS_PROPERTY_ID,RS_CLIENT_ID,RS_USER_ID,RS_TOKEN,RS_CHANGED_DATE,RS_FINAL_VALUE)');
    $binaryDb->exec("INSERT INTO rs_property_files VALUES(9,50,1,101,'foreign',3,x'616263')");
}
function binaryEndpoint($name, $body) {
    global $binaryBody, $binaryEndpoints, $mysqli, $RSMcreatedItemIDs, $RSallowDebug;
    $binaryBody = $body;
    try { eval($binaryEndpoints[$name]); } catch (BinaryResponse $response) { return $response; }
    throw new RuntimeException('Endpoint returned without a response');
}
function binaryV1Endpoint($name, $data, $itemID = '1') {
    global $binaryV1Endpoints, $mysqli, $RSMcreatedItemIDs, $RSallowDebug, $RSuserID, $cstRS_POST, $cstClientID, $cstRStoken;
    $cstRS_POST = 'binary_post'; $cstClientID = 'clientID'; $cstRStoken = 'RStoken';
    $GLOBALS[$cstRS_POST] = array('clientID' => 7, 'RStoken' => "token'quoted", 'RSdata' => $data, 'RSitemID' => $itemID);
    try { eval($binaryV1Endpoints[$name]); } catch (BinaryResponse $response) { return $response; }
    throw new RuntimeException('V1 endpoint returned without a response');
}
function binaryV1Transport($endpoint, $values) {
    if ($endpoint === 'api_updateItems') return base64_encode(json_encode(array('1' => $values)));
    $fields = array();
    foreach ($values as $id => $value) $fields[] = $id . ':' . base64_encode($value);
    return implode(';', $fields);
}
function binaryAdminEndpoint($path, $parameters) {
    global $binaryAdminEndpoints, $mysqli, $RSMcreatedItemIDs, $RSMupdatedItemIDs, $RSMsplitTriggers, $RSallowDebug, $RSuserID,
        $cstRS_POST, $cstClientID, $cstName, $cstItemtypeID, $cstItemtypeIcon;
    $cstRS_POST = 'binary_post'; $cstClientID = 'clientID'; $cstName = 'name'; $cstItemtypeID = 'itemtypeID'; $cstItemtypeIcon = 'itemtypeIcon';
    $GLOBALS[$cstRS_POST] = array_merge(array('clientID' => '7'), $parameters);
    try { eval($binaryAdminEndpoints[$path]); } catch (BinaryResponse $response) { return $response; }
    throw new RuntimeException('Admin endpoint returned without a response');
}
function binaryRejects($value, $class = InvalidArgumentException::class) {
    try { RSparseBinaryPropertyValue($value); } catch (Throwable $error) {
        binaryAssert($error instanceof $class, 'Unexpected validation error'); return;
    }
    throw new RuntimeException('Invalid upload was accepted');
}
function getClientPropertyReferredItemType($id, $client) { return 50; }
function getItems($type, $client, $unused, $id) { return verifyItemExists($id, $type, $client) ? array(array('ID'=>$id)) : array(); }
function getFilterItemType($client, $filter) { return 0; }
function getMainPropertyID($type, $client) { return 100; }
function getMainPropertyValue($type, $item, $client) {
    return $GLOBALS['binaryDb']->querySingle('SELECT RS_DATA FROM rs_property_text WHERE RS_CLIENT_ID=' . intval($client) . ' AND RS_ITEM_ID=' . intval($item));
}
function RSgetNextIntegerLockName($client, $property, $year, $series) { return 'binary-test-sequence'; }
function RSacquireNextIntegerLock($name) { return true; }
function RSreleaseNextIntegerLock($name) { $GLOBALS['binaryReleasedSequenceLock'] = true; }
function RSgetNextIntegerPropertyValue($client, $type, $property, $year, $series) { return 2; }
function RSError($message) { }

$testLog = tempnam(sys_get_temp_dir(), 'rsm-binary-test-');
ini_set('error_log', $testLog);
try {
    // Existing three-argument calls remain secure, with root and parent transport
    // unchanged. A failed property or audit must return NOK and roll back.
    $treePath = 'genericTreeListBox/classGenericTreeListBox_createItem.php';
    foreach (array('0', '1') as $parent) {
        binaryReset();
        $response = binaryAdminEndpoint($treePath, array('itemTypeID'=>'50', 'parentID'=>$parent,
            'parentPropertyID'=>$parent === '0' ? '0' : '100', 'filterID'=>''));
        binaryAssert($response->body['result'] === 'OK' && $response->body['nodeID'] === 2, 'Tree creation failed');
        binaryAssert(verifyItemExists(2,50,7), 'Tree reported success without storing item');
    }
    if (in_array('--legacy-manager', $argv, true)) {
        binaryReset();
        $response = binaryV1Endpoint('api_createItem', '100:' . base64_encode('staged API creation'));
        binaryAssert($response->body['itemID'] === '2' && verifyItemExists(2,50,7), 'Prepared v1 caller failed with old manager');
        binaryReset();
        $response = binaryAdminEndpoint('shared/classDataManager_createItems.php', array('properties'=>'100;' . base64_encode('staged DataManager creation')));
        binaryAssert($response->body['itemIDs'] === '2' && verifyItemExists(2,50,7), 'Prepared DataManager caller failed with old manager');
        binaryReset();
        binaryAssert(binaryEndpoint('create', array((object)array('101'=>'bad:GG')))->getCode() === 400, 'Prepared v2 accepted invalid transport');
        binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'Prepared v2 wrote before rejecting input');
        echo "PASS: prepared tree, v1 and DataManager callers with pre-hardening manager/properties\n";
        return;
    }
    foreach (array('rs_property_images', 'rs_audit_trail_property_text', 'begin', 'commit') as $failure) {
        binaryReset(); $GLOBALS['binaryFail'] = $failure; $GLOBALS['binaryAudited'] = array(100);
        $response = binaryAdminEndpoint($treePath, array('itemTypeID'=>'50','parentID'=>'0','parentPropertyID'=>'0','filterID'=>''));
        binaryAssert($response->body['result'] === 'NOK', 'Tree reported false success');
        binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1 && $RSMcreatedItemIDs === array(), 'Tree failure left partial data or events');
    }
    binaryReset();
    foreach (array('f.bin:00aAFF', 'f.bin:0x00aAFF', '0X00aAFF', '', 'f.bin:', '0x', 'ñ.bin:00') as $value) {
        $parsed = RSparseBinaryPropertyValue($value);
        binaryAssert(strlen(RSdecodeBinaryPropertyValue($parsed)) === $parsed['size'], 'Decoded size mismatch');
    }
    foreach (array('f:0', 'f:gg', 'f:00,1);--', "f:00\n", 'f:00:ff', "f:00\0", "bad\0.bin:00", array(), null, 123) as $value) binaryRejects($value);
    binaryRejects(str_repeat('a', 256) . ':00');
    binaryAssert(RSparseBinaryPropertyValue(str_repeat('ñ', 255) . ':00')['size'] === 1, 'Valid Unicode filename rejected');
    $RSmaxBinaryPropertyBytes = 2;
    binaryRejects('f:000000', LengthException::class);
    binaryAssert(RSparseBinaryPropertyValue('f:0000')['size'] === 2, 'Exact size limit rejected');

    // Exercise the real 32 MiB boundary, including the fallback for old configs.
    binaryReset();
    $boundary = str_repeat('00', 32 * 1024 * 1024);
    binaryAssert(RSparseBinaryPropertyValue($boundary)['size'] === 32 * 1024 * 1024, '32 MiB upload rejected');
    unset($RSmaxBinaryPropertyBytes);
    binaryAssert(RSparseBinaryPropertyValue($boundary)['size'] === 32 * 1024 * 1024, 'Default limit is not 32 MiB');
    unset($boundary);
    $oversized = str_repeat('00', 32 * 1024 * 1024 + 1);
    binaryRejects($oversized, LengthException::class);
    unset($oversized);

    binaryReset();
    $name = "quo'ted\"; DROP TABLE rs_items;--ñ.bin";
    foreach (array(101 => 'file', 102 => 'image') as $id => $type) {
        $GLOBALS['binaryAudited'] = array($id);
        binaryAssert(_dbInsertItemPropertyValue(50, 1, $id, $type, $name . ':00ff0027', 7), 'Valid binary insert failed');
        $row = $binaryDb->querySingle('SELECT * FROM ' . $propertiesTables[$type] . ' WHERE RS_CLIENT_ID=7', true);
        binaryAssert($row['RS_NAME'] === $name && $row['RS_DATA'] === "\0\xff\0'" && $row['RS_SIZE'] === 4, 'Binary/name insert did not round-trip');
        $audit = $binaryDb->querySingle('SELECT * FROM ' . $auditTrailPropertiesTables[$type], true);
        binaryAssert($audit['RS_FINAL_NAME'] === $name && $audit['RS_FINAL_VALUE'] === $row['RS_DATA'] && $audit['RS_TOKEN'] === "token'quoted", 'Audit parameters did not round-trip');
        binaryAssert(setDataPropertyValueByID($id,50,1,7,$name,'0x0100ff',$type,3) === 0, 'Valid binary replace failed');
        binaryAssert(setDataPropertyValueByID($id,50,1,7,$name,'00,999);--',$type,3) === -5, 'SQL-shaped data accepted');
        binaryAssert($binaryDb->querySingle('SELECT RS_DATA FROM ' . $propertiesTables[$type] . ' WHERE RS_CLIENT_ID=7') === "\x01\0\xff", 'Rejected upload changed existing data');
        binaryAssert(!_dbInsertItemPropertyValue(50,2,$id,$type,'f:abc',7), 'Odd-length create accepted');
    }
    binaryAssert($binaryDb->querySingle('SELECT RS_NAME FROM rs_property_files WHERE RS_CLIENT_ID=9') === 'foreign', 'Foreign client changed');
    $RSmaxBinaryPropertyBytes = 1;
    $statementCount = count($mysqli->statements);
    binaryAssert(setDataPropertyValueByID(101,50,1,7,'oversized','0000','file') === -5, 'Direct update size limit bypassed');
    binaryAssert(!_dbInsertItemPropertyValue(50,2,101,'file','oversized:0000',7), 'Direct insert size limit bypassed');
    binaryAssert(count($mysqli->statements) === $statementCount, 'Oversized value reached SQL preparation');
    foreach ($mysqli->statements as $statement) binaryAssert($statement->closed, 'Statement was not closed');

    binaryReset();
    $RSmaxBinaryPropertyBytes = 200000;
    $data = str_repeat("\0\xffx", 30000);
    binaryAssert(setDataPropertyValueByID(101,50,1,7,'large',bin2hex($data),'file') === 0, 'Chunked upload failed');
    binaryAssert($GLOBALS['binaryChunkCount'] === 2, 'Upload was not streamed in chunks');
    binaryAssert($binaryDb->querySingle('SELECT RS_DATA FROM rs_property_files WHERE RS_CLIENT_ID=7') === $data, 'Chunked bytes changed');
    binaryAssert(setDataPropertyValueByID(101,50,1,7,'empty','','file') === 0, 'Empty BLOB write failed');
    binaryAssert($binaryDb->querySingle('SELECT length(RS_DATA) FROM rs_property_files WHERE RS_CLIENT_ID=7') === 0, 'Empty BLOB became NULL');
    $GLOBALS['binaryFail'] = 'send';
    binaryAssert(setDataPropertyValueByID(101,50,1,7,'new','00','file') === -3, 'Streaming failure ignored');

    foreach (array('rs_property_images', 'rs_audit_trail_property_files', 'UPDATE rs_item_types', 'INSERT INTO rs_items', 'metadata', 'begin', 'commit') as $failure) {
        binaryReset(); $GLOBALS['binaryFail'] = $failure; $GLOBALS['binaryAudited'] = array(101);
        binaryAssert(createItem(7,array(array('ID'=>101,'value'=>'file:00'))) === 0, 'Failed creation returned an ID: ' . $failure);
        binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'Partial item remained: ' . $failure);
        binaryAssert($binaryDb->querySingle('SELECT RS_LAST_ITEM_ID FROM rs_item_types') === 1, 'Counter not rolled back: ' . $failure);
        binaryAssert($GLOBALS['RSMcreatedItemIDs'] === array(), 'Rolled-back events remained');
        binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_property_files WHERE RS_CLIENT_ID=7') === 0, 'Partial property remained');
    }
    binaryReset(); $GLOBALS['binaryAudited'] = array(100);
    binaryAssert(createItem(7,array(array('ID'=>100,'value'=>"quo'ted text"))) === 2, 'Text/default binary creation regressed');
    binaryAssert($binaryDb->querySingle('SELECT RS_FINAL_VALUE FROM rs_audit_trail_property_text') === "quo'ted text", 'Text audit changed');
    binaryReset(); $GLOBALS['binaryDefaults'][102] = 'invalid:GG';
    binaryAssert(createItem(7,array(array('ID'=>101,'value'=>'f:00'))) === 0, 'Invalid binary default accepted');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'Invalid default left an item');

    // A caller such as the subaccount allocator owns the outer transaction.
    binaryReset();
    binaryAssert($mysqli->begin_transaction(), 'Unable to start caller transaction');
    binaryAssert(RScreateItemInTransaction(7, array(array('ID'=>100,'value'=>'outer transaction')), 50) === 2, 'Caller-owned creation failed');
    binaryAssert($mysqli->commits === 0, 'createItem committed the caller transaction');
    binaryAssert($mysqli->rollback(), 'Caller rollback failed');
    binaryAssert(!verifyItemExists(2,50,7), 'Caller rollback left the item');
    binaryAssert((new ReflectionFunction('createItem'))->getNumberOfParameters() === 3, 'Public creation signature changed');
    binaryReset();
    binaryAssert($mysqli->begin_transaction(), 'Unable to start failing caller transaction');
    $GLOBALS['binaryFail'] = 'rs_property_images';
    binaryAssert(RScreateItemInTransaction(7, array(array('ID'=>100,'value'=>'outer failure')), 50) === 0, 'Failed inner writer returned success');
    binaryAssert($mysqli->commits === 0 && $mysqli->rollbacks === 0, 'Inner writer controlled the caller transaction');
    binaryAssert($mysqli->rollback(), 'Failed caller rollback failed');
    binaryAssert(!verifyItemExists(2,50,7), 'Failed caller left the item');

    // Exercise the production subaccount allocator with the shared inner writer.
    // Its final verification and commit can still fail after item creation.
    foreach (array('', 'commit', 'rs_property_images', 'verification') as $failure) {
        binaryReset(); $GLOBALS['binaryTypes'][100] = 'integer';
        $binaryDb->exec('CREATE TABLE rs_property_integers (RS_ITEMTYPE_ID,RS_ITEM_ID,RS_DATA,RS_PROPERTY_ID,RS_CLIENT_ID)');
        $GLOBALS['binaryFail'] = $failure === 'verification' ? '' : $failure;
        $GLOBALS['binaryReleasedSequenceLock'] = false;
        $next = RSallocateNextIntegerPropertyValue(7,50,100, function ($next) use ($failure) {
            $item = RScreateItemInTransaction(7,array(array('ID'=>100,'value'=>(string)$next)),50);
            return $item > 0 && $failure !== 'verification';
        });
        binaryAssert($GLOBALS['binaryReleasedSequenceLock'], 'Allocator did not release its lock');
        if ($failure === '') {
            binaryAssert($next === 2 && verifyItemExists(2,50,7) && $mysqli->commits === 1, 'Allocator success failed');
        } else {
            binaryAssert($next === false && !verifyItemExists(2,50,7), 'Allocator failure left an item: ' . $failure);
            binaryAssert($RSMcreatedItemIDs === array(), 'Allocator rollback left creation events: ' . $failure);
            binaryAssert($binaryDb->querySingle('SELECT RS_LAST_ITEM_ID FROM rs_item_types') === 1, 'Allocator rollback left the item counter');
        }
    }

    foreach (array('create','update') as $endpoint) {
        binaryReset();
        $row = (object)array('101'=>'f:00'); if ($endpoint === 'update') $row->ID = '1';
        binaryAssert(binaryEndpoint($endpoint, array($row))->getCode() === 200, 'Valid endpoint upload failed');
        $row->{'101'} = 'f:00,999);--';
        binaryAssert(binaryEndpoint($endpoint, array($row))->getCode() === 400, 'Endpoint injection not rejected');
        $RSmaxBinaryPropertyBytes = 1; $row->{'101'} = 'f:0000';
        binaryAssert(binaryEndpoint($endpoint, array($row))->getCode() === 413, 'Endpoint size limit not enforced');
    }
    binaryReset();
    $response = binaryEndpoint('create', array((object)array('101'=>'first:00'), (object)array('102'=>'bad:GG')));
    binaryAssert($response->getCode() === 400 && $binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'Batch validation caused partial creates');
    binaryReset(); $GLOBALS['binaryFail'] = 'rs_property_images';
    binaryAssert(binaryEndpoint('create', array((object)array('101'=>'first:00')))->getCode() === 500, 'Database failure status missing');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1 && $RSMcreatedItemIDs === array(), 'API batch rollback failed');
    binaryReset(); $GLOBALS['binaryFailAfter'] = 5;
    binaryAssert(binaryEndpoint('create', array((object)array('101'=>'first:00'), (object)array('101'=>'second:ff')))->getCode() === 500, 'Second item failure status missing');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1 && $RSMcreatedItemIDs === array(), 'Earlier batch item was not rolled back');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_property_files WHERE RS_CLIENT_ID=7') === 0, 'Earlier batch BLOB remained');
    binaryReset(); $GLOBALS['binaryFail'] = 'commit';
    binaryAssert(binaryEndpoint('create', array((object)array('101'=>'f:00')))->getCode() === 500, 'API commit failure status missing');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1 && $RSMcreatedItemIDs === array(), 'API commit failure not rolled back');
    binaryReset();
    $response = binaryEndpoint('update', array((object)array('ID'=>'1','100'=>'text'), (object)array('ID'=>'1','101'=>'f:GG')));
    binaryAssert($response->getCode() === 400 && $GLOBALS['binaryTextUpdates'] === 0, 'Invalid later upload allowed earlier updates');
    binaryReset(); $GLOBALS['binaryDenied'] = array(101);
    binaryAssert(binaryEndpoint('update', array((object)array('ID'=>'1','101'=>'f:00')))->getCode() === 400, 'Permissions bypassed');
    binaryReset(); $GLOBALS['binaryScopeDenied'] = true;
    binaryAssert(binaryEndpoint('update', array((object)array('ID'=>'1','101'=>'f:00')))->getCode() === 403, 'Customer scope bypassed');
    binaryReset();
    binaryAssert(binaryEndpoint('update', array((object)array('ID'=>'1','101'=>'f:')))->getCode() === 200, 'Empty upload deletion regressed');

    foreach (array('api_createItem','api_updateItem','api_updateItems') as $endpoint) {
        foreach (array(101 => 'file', 102 => 'image') as $id => $type) {
            binaryReset();
            $name = "quo'ted\"ñ.bin";
            $response = binaryV1Endpoint($endpoint, binaryV1Transport($endpoint, array($id => $name . ':00ff0027')));
            binaryAssert($response->getCode() === 200 && $response->body['result'] === 'OK', 'V1 success contract changed: ' . $endpoint);
            if ($endpoint === 'api_createItem') binaryAssert($response->body['itemID'] === '2', 'V1 created ID format changed');
            $row = $binaryDb->querySingle('SELECT * FROM ' . $propertiesTables[$type] . ' WHERE RS_CLIENT_ID=7', true);
            $expectedName = $endpoint === 'api_createItem' ? replaceUtf8Characters($name) : $name;
            binaryAssert($row['RS_NAME'] === $expectedName && $row['RS_DATA'] === "\0\xff\0'", 'V1 binary/name compatibility changed');
            binaryAssert(binaryV1Endpoint($endpoint, binaryV1Transport($endpoint, array($id => 'file:00,999);--')))->getCode() === 400, 'V1 SQL-shaped data accepted');
            binaryAssert(binaryV1Endpoint($endpoint, binaryV1Transport($endpoint, array($id => 'file:00:ff')))->getCode() === 400, 'V1 silently truncated malformed upload');
            $RSmaxBinaryPropertyBytes = 1;
            binaryAssert(binaryV1Endpoint($endpoint, binaryV1Transport($endpoint, array($id => 'file:0000')))->getCode() === 413, 'V1 size limit bypassed');
        }
    }
    binaryReset();
    $name = "quo'ted\"ñ.bin";
    binaryAssert(binaryEndpoint('create', array((object)array('101' => $name . ':00ff')))->getCode() === 200, 'V2 quoted filename rejected');
    binaryAssert($binaryDb->querySingle('SELECT RS_NAME FROM rs_property_files WHERE RS_CLIENT_ID=7') === replaceUtf8Characters($name), 'V2 legacy filename encoding changed');
    binaryReset();
    $response = binaryV1Endpoint('api_updateItem', binaryV1Transport('api_updateItem', array(100 => 'first', 101 => 'bad:GG')));
    binaryAssert($response->getCode() === 400 && $GLOBALS['binaryTextUpdates'] === 0, 'V1 invalid upload allowed earlier property writes');
    binaryReset();
    $response = binaryV1Endpoint('api_updateItems', base64_encode(json_encode(array('1' => array(100 => 'first'), '2' => array(101 => 'bad:GG')))));
    binaryAssert($response->getCode() === 400 && $GLOBALS['binaryTextUpdates'] === 0, 'V1 invalid later item allowed earlier writes');
    binaryReset();
    $first = binaryV1Transport('api_createItem', array(101 => 'first:00'));
    $second = binaryV1Transport('api_createItem', array(102 => 'bad:GG'));
    binaryAssert(binaryV1Endpoint('api_createItem', $first . ',' . $second)->getCode() === 400, 'V1 invalid later create accepted');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'V1 invalid later create left earlier item');
    binaryReset();
    binaryAssert(binaryV1Endpoint('api_updateItem', '101:not!base64')->getCode() === 400, 'V1 malformed Base64 accepted');
    foreach (array('api_updateItem', 'api_updateItems') as $endpoint) {
        binaryReset();
        binaryAssert(binaryV1Endpoint($endpoint, binaryV1Transport($endpoint, array(101 => 'f:')))->body['result'] === 'OK', 'V1 empty upload deletion regressed');
    }
    // Check the actual legacy HTTP helper, whose default previously forced 400.
    $httpHelper = binaryExtractFunction($tools, 'dieWithError');
    $process = proc_open(array(PHP_BINARY, '-r', $httpHelper . '; register_shutdown_function(function () { echo " STATUS=" . http_response_code(); }); dieWithError(413);'), array(1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
    binaryAssert(is_resource($process), 'Unable to test legacy HTTP helper');
    $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($process);
    binaryAssert($exit === 0 && $errors === '' && $output === '413 Payload Too Large STATUS=413', 'Legacy helper did not emit real HTTP 413');
    // Item type icons use the same validator and prepared binary writer.
    $createIconPath = 'itemsManager/classLbxItemtypesList_createItemtype.php';
    $updateIconPath = 'itemsManager/classLbxItemtypesList_updateItemtype.php';
    binaryReset();
    $name = "quo'ted\"; DROP TABLE rs_items;--ñ";
    $response = binaryAdminEndpoint($createIconPath, array('itemtypeName' => base64_encode($name), 'itemtypeIcon' => '00ff0027'));
    binaryAssert($response->body === array('result'=>'OK','itemtypeID'=>51,'itemtypeName'=>$name,'itemtypeIcon'=>'00ff0027'), 'Icon create success fields changed');
    $row = $binaryDb->querySingle('SELECT * FROM rs_item_types WHERE RS_ITEMTYPE_ID=51', true);
    binaryAssert($row['RS_NAME'] === $name && $row['RS_ICON'] === "\0\xff\0'", 'Icon did not round-trip safely');
    binaryAssert(binaryAdminEndpoint($createIconPath, array('itemtypeName'=>base64_encode('bad'),'itemtypeIcon'=>'00,123);--'))->body['result'] === 'NOK', 'Icon SQL injection accepted');
    $binaryDb->exec("INSERT INTO rs_item_types (RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ICON) VALUES (9,50,x'616263')");
    binaryAssert(binaryAdminEndpoint($updateIconPath, array('itemtypeID'=>'50','name'=>base64_encode($name),'itemtypeIcon'=>'ff00'))->body['result'] === 'OK', 'Icon update failed');
    binaryAssert($binaryDb->querySingle('SELECT RS_ICON FROM rs_item_types WHERE RS_CLIENT_ID=7 AND RS_ITEMTYPE_ID=50') === "\xff\0", 'Updated icon bytes changed');
    binaryAssert($binaryDb->querySingle('SELECT RS_ICON FROM rs_item_types WHERE RS_CLIENT_ID=9') === 'abc', 'Foreign icon changed');
    binaryAssert(binaryAdminEndpoint($updateIconPath, array('itemtypeID'=>'50 OR 1=1','name'=>base64_encode('bad'),'itemtypeIcon'=>'00'))->body['result'] === 'NOK', 'Icon identifier injection accepted');
    $RSmaxBinaryPropertyBytes = 1;
    binaryAssert(binaryAdminEndpoint($createIconPath, array('itemtypeName'=>base64_encode('large'),'itemtypeIcon'=>'0000'))->body['result'] === 'NOK', 'Icon size limit bypassed');
    binaryAssert(binaryAdminEndpoint($updateIconPath, array('itemtypeID'=>'50','name'=>base64_encode('large'),'itemtypeIcon'=>'0000'))->body['result'] === 'NOK', 'Icon update size limit bypassed');
    binaryReset(); $GLOBALS['binaryFail'] = 'INSERT INTO rs_item_types';
    binaryAssert(binaryAdminEndpoint($createIconPath, array('itemtypeName'=>base64_encode('failed'),'itemtypeIcon'=>'00'))->body['result'] === 'NOK', 'Icon insert failure reported OK');

    // Global images and scalar names/values are bound, and replacements roll back.
    $globalPath = 'Globals/classLbxGlobals_update.php';
    binaryReset();
    $globalName = "quo'ted\"; DELETE FROM rs_globals;--";
    $response = binaryAdminEndpoint($globalPath, array('var0' => '2 ' . base64_encode($globalName) . ' ' . base64_encode('00ff') . ' 1'));
    binaryAssert($response->body === array('result'=>'OK'), 'Global image success contract changed');
    $row = $binaryDb->querySingle('SELECT * FROM rs_globals WHERE RS_CLIENT_ID=7', true);
    binaryAssert($row['RS_NAME'] === $globalName && $row['RS_VALUE'] === "\0\xff" && $row['RS_IMAGE'] === 1, 'Global image or name changed');
    $response = binaryAdminEndpoint($globalPath, array('var0' => '2 ' . base64_encode($globalName) . ' ' . base64_encode('ff') . ' 1'));
    binaryAssert($response->body === array('result'=>'NOK','var'=>$globalName), 'Global conflict result changed');
    $update = '1 ' . base64_encode($globalName) . ' ' . base64_encode('renamed') . ' ' . base64_encode('GG') . ' 1';
    binaryAssert(binaryAdminEndpoint($globalPath, array('var0'=>$update))->body['result'] === 'NOK', 'Invalid global image accepted');
    binaryAssert($binaryDb->querySingle('SELECT RS_NAME FROM rs_globals') === $globalName, 'Invalid image removed old global');
    $RSmaxBinaryPropertyBytes = 1;
    $update = '1 ' . base64_encode($globalName) . ' ' . base64_encode('renamed') . ' ' . base64_encode('0000') . ' 1';
    binaryAssert(binaryAdminEndpoint($globalPath, array('var0'=>$update))->body['result'] === 'NOK', 'Global size limit bypassed');
    binaryAssert($binaryDb->querySingle('SELECT RS_NAME FROM rs_globals') === $globalName, 'Oversized image removed old global');
    $GLOBALS['binaryFail'] = 'INSERT INTO rs_globals';
    $update = '1 ' . base64_encode($globalName) . ' ' . base64_encode('renamed') . ' ' . base64_encode('ff') . ' 1';
    binaryAssert(binaryAdminEndpoint($globalPath, array('var0'=>$update))->body['result'] === 'NOK', 'Global failure reported OK');
    binaryAssert($binaryDb->querySingle('SELECT RS_NAME FROM rs_globals') === $globalName, 'Global replacement rollback failed');
    binaryReset();
    $scalarValue = "quo'ted\"; DROP TABLE rs_globals;--";
    binaryAssert(binaryAdminEndpoint($globalPath, array('var0'=>'2 ' . base64_encode('text') . ' ' . base64_encode($scalarValue) . ' 0'))->body['result'] === 'OK', 'Scalar global failed');
    binaryAssert($binaryDb->querySingle('SELECT RS_VALUE FROM rs_globals') === $scalarValue, 'Scalar global was interpolated');

    // CSV values (including filenames) go through the shared property writer.
    $csvPath = 'itemsManager/classWndImportCSV_import.php';
    $csv = array('itemTypeID'=>'50','itemIDs'=>'','propertyIDs'=>'100,101,102','overwrite'=>'0','allowTriggerEvents'=>'1',
        'propertiesList'=>base64_encode($scalarValue) . ' ' . base64_encode($name . '.bin:00ff') . ' ' . base64_encode('image.png:ff00'));
    binaryReset();
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body === array('result'=>'OK'), 'CSV success contract changed');
    $row = $binaryDb->querySingle('SELECT * FROM rs_property_files WHERE RS_CLIENT_ID=7', true);
    binaryAssert($row['RS_NAME'] === $name . '.bin' && $row['RS_DATA'] === "\0\xff" && $row['RS_SIZE'] === 2, 'CSV binary metadata not stored correctly');
    binaryAssert($binaryDb->querySingle('SELECT RS_DATA FROM rs_property_text') === $scalarValue, 'CSV text was not parameterized');
    binaryAssert($RSMcreatedItemIDs === array('50,2'), 'CSV creation events changed');
    $csv['overwrite'] = '1'; $csv['itemIDs'] = '2'; $csv['propertyIDs'] = '101'; $csv['propertiesList'] = base64_encode('updated:ff');
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] === 'OK', 'CSV binary overwrite failed');
    binaryAssert($binaryDb->querySingle('SELECT RS_DATA FROM rs_property_files WHERE RS_CLIENT_ID=7') === "\xff", 'CSV overwrite did not replace BLOB');
    binaryAssert($binaryDb->querySingle('SELECT RS_DATA FROM rs_property_images WHERE RS_CLIENT_ID=7') === "\xff\0", 'CSV overwrite changed an omitted property');
    $csv['overwrite'] = '0';
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] === 'ERROR1', 'CSV existing-ID conflict changed');
    $csv['itemIDs'] = '3,3'; $csv['propertiesList'] .= ':' . $csv['propertiesList'];
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] === 'ERROR2', 'CSV duplicate-ID conflict changed');
    binaryReset();
    $csv['itemIDs'] = ''; $csv['propertiesList'] = base64_encode('bad:00,123);--');
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] !== 'OK', 'CSV injection accepted');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1, 'CSV validation left partial items');
    $RSmaxBinaryPropertyBytes = 1; $csv['propertiesList'] = base64_encode('large:0000');
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] !== 'OK', 'CSV size limit bypassed');
    binaryReset(); $GLOBALS['binaryFail'] = 'rs_property_images'; $csv['propertiesList'] = base64_encode('valid:00');
    binaryAssert(binaryAdminEndpoint($csvPath, $csv)->body['result'] === 'ERROR0', 'CSV storage failure code changed');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_items') === 1 && $RSMcreatedItemIDs === array(), 'CSV failure not rolled back');

    // DataManager must not return OK if a shared writer rejects the upload.
    $editPath = 'shared/classDataManager_editItems.php';
    binaryReset();
    $entry = '1;101;' . base64_encode('00ff') . ';' . base64_encode($name . '.bin') . ';' . base64_encode('2');
    binaryAssert(binaryAdminEndpoint($editPath, array('entries'=>$entry))->body['result'] === 'OK', 'DataManager success changed');
    $GLOBALS['binaryFail'] = 'REPLACE INTO rs_property_files';
    binaryAssert(binaryAdminEndpoint($editPath, array('entries'=>$entry))->body['result'] === 'NOK', 'DataManager failure reported OK');
    binaryReset();
    $badEntry = '1;101;' . base64_encode('GG') . ';' . base64_encode('bad');
    binaryAssert(binaryAdminEndpoint($editPath, array('entries'=>$entry . ',' . $badEntry))->body['result'] === 'NOK', 'DataManager invalid later upload accepted');
    binaryAssert($binaryDb->querySingle('SELECT COUNT(*) FROM rs_property_files WHERE RS_CLIENT_ID=7') === 0, 'DataManager validation allowed partial writes');
    binaryReset(); $RSmaxBinaryPropertyBytes = 1;
    binaryAssert(binaryAdminEndpoint($editPath, array('entries'=>$entry))->body['result'] === 'NOK', 'DataManager size limit bypassed');
    binaryReset();
    binaryAssert(binaryAdminEndpoint('shared/classDataManager_createItems.php', array('properties'=>'101;' . base64_encode('invalid:GG')))->body['result'] === 'NOK', 'DataManager creation failure not reported');

    echo "PASS: SEC-02 validation, API/admin uploads, shared prepared writes, 32 MiB limit, compatibility and rollback\n";
} finally { unlink($testLog); }
