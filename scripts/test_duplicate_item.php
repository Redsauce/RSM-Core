<?php
// Behavioral endpoint tests using the real duplicateItem implementation and an
// in-memory SQL adapter. No bootstrap credentials or running server are needed.
function duplicateAssert($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function duplicateExtractFunction($source, $name) {
    $start = strpos($source, 'function ' . $name . '(');
    duplicateAssert($start !== false, 'Missing function ' . $name);
    $depth = 0;
    $started = false;
    $result = '';
    foreach (token_get_all('<?php ' . substr($source, $start)) as $token) {
        if (is_array($token) && $token[0] === T_OPEN_TAG) continue;
        $result .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $started = true; }
        if ($token === '}') $depth--;
        if ($started && $depth === 0) return $result;
    }
    throw new RuntimeException('Unclosed function ' . $name);
}
class DuplicateResponse extends Exception {
    public $payload;
    public function __construct($code, $payload) { parent::__construct('', $code); $this->payload = $payload; }
}
class DuplicateMemoryDb {
    private $snapshot;
    public $commits = 0;
    public $rollbacks = 0;
    public function begin_transaction() { $this->snapshot = $GLOBALS['copyRows']; return true; }
    public function commit() { if ($GLOBALS['copyFail'] === 'commit') return false; $this->commits++; return true; }
    public function rollback() { $GLOBALS['copyRows'] = $this->snapshot; $this->rollbacks++; return true; }
}
function RSQuery($sql) {
    global $copyRows, $copyFail;
    if (strpos($sql, 'SELECT RS_LAST_ITEM_ID') === 0) return (object)array('num_rows' => 1);
    if (strpos($sql, 'UPDATE rs_item_types') === 0) {
        if ($copyFail === 'counter') return false;
        preg_match('/GREATEST\(RS_LAST_ITEM_ID, (\d+)\)/', $sql, $match);
        $copyRows['counter'] = max($copyRows['counter'], intval($match[1]));
        return true;
    }
    if (strpos($sql, 'INSERT INTO rs_items ') === 0) {
        if ($copyFail === 'item') return false;
        preg_match_all('/\((\d+),(\d+),(\d+)\)/', $sql, $matches, PREG_SET_ORDER);
        foreach ($matches as $row) {
            $key = $row[3] . ':' . $row[1] . ':' . $row[2];
            if (isset($copyRows['items'][$key])) return false;
            $copyRows['items'][$key] = true;
        }
        return true;
    }
    if (preg_match('/^INSERT INTO (\w+) .* SELECT /', $sql, $tableMatch)) {
        $table = $tableMatch[1];
        if ($copyFail === $table) return false;
        preg_match_all('/SELECT (\d+),(\d+),(\d+),(\d+),.*? FROM \w+ WHERE RS_CLIENT_ID = (\d+) AND RS_ITEMTYPE_ID = (\d+) AND RS_PROPERTY_ID = (\d+) AND RS_ITEM_ID = (\d+)/', $sql, $selects, PREG_SET_ORDER);
        duplicateAssert(count($selects) > 0, 'Copy query must carry all identity filters');
        foreach ($selects as $select) {
            $source = "$select[5]:$select[6]:$select[8]:$select[7]";
            $target = "$select[4]:$select[1]:$select[2]:$select[3]";
            if (isset($copyRows[$table][$source])) $copyRows[$table][$target] = $copyRows[$table][$source];
        }
        return true;
    }
    throw new RuntimeException('Unexpected SQL: ' . $sql);
}
function getNextItemTypeIdentification($type, $client) { return $GLOBALS['copyRows']['counter'] + 1; }
function getClientItemTypeProperties($type, $client, $exclude = 0, $failOnError = false) {
    if ($failOnError && $GLOBALS['copyFail'] === 'metadata') throw new RuntimeException('Metadata read failed');
    return array_values(array_filter($GLOBALS['copyProperties'], function ($property) use ($exclude) { return !$exclude || !$property['excluded']; }));
}
function getMainPropertyID($type, $client) { return $GLOBALS['copyMain']; }
function handleApiCorsPreflight($method) { if ($GLOBALS['copyMethod'] === 'OPTIONS') throw new DuplicateResponse(204, null); }
function setAuthorizationTokenOnGlobals() { }
function checkCorrectRequestMethod($method) { if ($GLOBALS['copyMethod'] !== $method) throw new DuplicateResponse(405, null); }
function getRequestBody() { return $GLOBALS['copyBody']; }
function getRStoken() { return 'test'; }
function RSclientFromToken($token) { return 7; }
function getRSuserID() { return 3; }
function parseITID($type, $client) { return in_array($type, array('8', 8, 'generic.type'), true) ? 8 : 0; }
function verifyItemExists($item, $type, $client) { return isset($GLOBALS['copyRows']['items']["$client:$type:$item"]); }
function RShasTokenPermission($token, $property, $permission) { return !in_array("$property:$permission", $GLOBALS['copyDenied'], true); }
function isPropertyVisible($user, $property, $client) { return $GLOBALS['copyVisible']; }
function RSisCustomerScopedToken($token) { return $GLOBALS['copyScoped']; }
function RSgetTokenCustomerDependencyPropertyID($token, $type, $client) { return 104; }
function RSitemMatchesTokenCustomerScope($token, $client, $type, $item) {
    return !$GLOBALS['copyScoped'] || (($GLOBALS['copyRows']['rs_property_identifiers']["$client:$type:$item:104"]['RS_DATA'] ?? '') === '99');
}
function RSError($message) { }
function returnJsonMessage($code, $message) { throw new DuplicateResponse($code, $message); }
function returnJsonResponse($json) { throw new DuplicateResponse(200, json_decode($json, true)); }

$root = dirname(__DIR__);
$manager = file_get_contents($root . '/Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
foreach (array('duplicateItem', 'RSlockItemTypeForDuplication') as $name) eval(duplicateExtractFunction($manager, $name));
$copyEndpoint = file_get_contents($root . '/Server/htdocs/AppController/commands_RSM/api/v2/items/duplicate.php');
$copyEndpoint = preg_replace('/^require_once .*;\R/m', '', substr($copyEndpoint, 5));
$propertiesTables = array('text'=>'rs_property_text', 'integer'=>'rs_property_integers', 'date'=>'rs_property_dates', 'identifier'=>'rs_property_identifiers', 'identifiers'=>'rs_property_multiIdentifiers', 'file'=>'rs_property_files', 'image'=>'rs_property_images');
function resetDuplicateFixture() {
    global $copyRows, $copyProperties, $copyBody, $copyMethod, $copyFail, $copyDenied, $copyScoped, $copyVisible, $copyMain, $mysqli, $RSallowDebug;
    $mysqli = new DuplicateMemoryDb();
    $RSallowDebug = false;
    $copyMethod = 'POST'; $copyFail = ''; $copyDenied = array(); $copyScoped = false; $copyVisible = false; $copyMain = 100;
    $copyBody = (object)array('itemTypeID'=>'8', 'itemID'=>'1');
    $copyRows = array('counter'=>5, 'items'=>array('7:8:1'=>true, '9:8:1'=>true, '7:9:1'=>true));
    $copyProperties = array();
    foreach (array(100=>'text',101=>'integer',102=>'date',103=>'text',104=>'identifier',105=>'identifiers',106=>'file',107=>'image',108=>'file',109=>'integer',110=>'text') as $id=>$type) {
        $copyProperties[] = array('id'=>$id,'type'=>$type,'excluded'=>$id === 103);
    }
    $values = array(100=>"Unicode ñ 'quoted' \\",101=>453,102=>'2026-09-10',103=>'DO NOT COPY',104=>'99',105=>'9,12',106=>"\0\xfffile",107=>"\0image",109=>0,110=>'');
    foreach ($copyProperties as $property) {
        $id = $property['id'];
        if (!array_key_exists($id, $values)) continue;
        $table = $GLOBALS['propertiesTables'][$property['type']];
        $row = array('RS_DATA'=>$values[$id]);
        if (in_array($property['type'], array('identifier','identifiers'))) $row['RS_ORDER'] = '3,7';
        if (in_array($property['type'], array('file','image'))) { $row['RS_NAME'] = "file:name'ñ"; $row['RS_SIZE'] = strlen($values[$id]); }
        $copyRows[$table]["7:8:1:$id"] = $row;
        $copyRows[$table]["9:8:1:$id"] = array('RS_DATA'=>'foreign client');
        $copyRows[$table]["7:9:1:$id"] = array('RS_DATA'=>'foreign type');
    }
}
function invokeDuplicateEndpoint() {
    global $mysqli, $RSallowDebug, $propertiesTables;
    try { eval($GLOBALS['copyEndpoint']); } catch (DuplicateResponse $response) { return array($response->getCode(), $response->payload); }
    throw new RuntimeException('Endpoint did not respond');
}

resetDuplicateFixture();
$before = $copyRows;
$response = invokeDuplicateEndpoint();
duplicateAssert($response === array(200,array('itemTypeID'=>8,'sourceItemID'=>1,'newItemID'=>6)), 'Response contract');
foreach ($copyProperties as $property) {
    $id = $property['id']; $table = $propertiesTables[$property['type']];
    if ($property['excluded'] || $id === 108) duplicateAssert(!isset($copyRows[$table]["7:8:6:$id"]), 'Excluded/missing property must stay absent');
    else duplicateAssert($copyRows[$table]["7:8:6:$id"] === $before[$table]["7:8:1:$id"], 'Value/metadata must be preserved for ' . $id);
}
foreach ($before as $table=>$rows) {
    if ($table === 'counter') continue;
    foreach ($rows as $key=>$row) duplicateAssert($copyRows[$table][$key] === $row, 'Existing data changed');
}
duplicateAssert(count($copyRows['items']) === 4, 'Exactly one item must be created');
duplicateAssert(invokeDuplicateEndpoint()[1]['newItemID'] === 7, 'Repeated request must get a new ID');

foreach (array(null, array(), (object)array(), (object)array('itemTypeID'=>'8','itemID'=>0), (object)array('itemTypeID'=>'8','itemID'=>'1,2'), (object)array('itemTypeID'=>'8','itemID'=>true), (object)array('itemTypeID'=>'8','itemID'=>'99999999999999999999999'), (object)array('itemTypeID'=>'8','itemID'=>1,'descendants'=>array()), (object)array('itemTypeID'=>array(),'itemID'=>1)) as $invalid) {
    resetDuplicateFixture(); $copyBody = $invalid; $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 400 && $copyRows === $before, 'Invalid request must not write');
}
resetDuplicateFixture(); $copyBody->itemTypeID = 'unknown'; duplicateAssert(invokeDuplicateEndpoint()[0] === 400,'Unknown type');
resetDuplicateFixture(); $copyBody->itemID = 999; duplicateAssert(invokeDuplicateEndpoint()[0] === 404,'Missing source');
resetDuplicateFixture(); $copyBody->itemTypeID = 'generic.type'; duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'Mapped type');
foreach (array(array('READ'), array('CREATE'), array('READ', 'CREATE')) as $deniedPermissions) {
    resetDuplicateFixture(); $RSallowDebug = true;
    foreach ($deniedPermissions as $permission) $copyDenied[] = '100:' . $permission;
    $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint() === array(403,
        'No permission to duplicate all eligible properties (clientID=7, itemTypeID=8, propertyID=100, failed=' . implode(',', $deniedPermissions) . ')'),
        'Debug response must identify the failing property and permission checks');
    duplicateAssert($copyRows === $before && $mysqli->rollbacks === 1, 'Diagnostic failure must roll back');
    $RSallowDebug = false;
    duplicateAssert(invokeDuplicateEndpoint() === array(403, ''), 'Non-debug response must not disclose permission details');
}
foreach (array('READ','CREATE') as $permission) {
    resetDuplicateFixture(); $copyDenied = array('100:'.$permission); $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 403 && $copyRows === $before,'Permission failure must not copy');
    $copyVisible = true; duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'Existing visibility fallback');
}
resetDuplicateFixture(); $copyDenied = array('103:READ','103:CREATE'); duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'Excluded properties need no copy permissions');
resetDuplicateFixture(); foreach ($copyProperties as &$property) $property['excluded'] = true; unset($property);
duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'All-excluded type with main-property access');
foreach ($copyRows as $table=>$rows) if ($table !== 'items' && $table !== 'counter') foreach ($rows as $key=>$row) duplicateAssert(strpos($key,'7:8:6:') !== 0,'No defaults for excluded properties');
$copyMain = 0; duplicateAssert(invokeDuplicateEndpoint()[0] === 403,'No permission anchor');
resetDuplicateFixture(); $copyScoped = true; duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'Allowed customer copy');
resetDuplicateFixture(); $copyScoped = true; $copyRows['rs_property_identifiers']['7:8:1:104']['RS_DATA'] = '100'; duplicateAssert(invokeDuplicateEndpoint()[0] === 403,'Other customer denied');
resetDuplicateFixture(); $copyScoped = true; foreach ($copyProperties as &$property) if ($property['id'] === 104) $property['excluded'] = true; unset($property);
duplicateAssert(invokeDuplicateEndpoint()[0] === 403,'Excluded customer dependency must not be injected');
foreach (array('metadata','item','rs_property_dates','counter','commit') as $failure) {
    resetDuplicateFixture(); $copyFail = $failure; $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint() === array(500,''),'Persistence failure must not report success or expose details');
    duplicateAssert($copyRows === $before && $mysqli->rollbacks === 1,'Persistence failure must roll back all writes');
}
resetDuplicateFixture(); $copyRows['items']['7:8:6'] = true; $before = $copyRows;
duplicateAssert(invokeDuplicateEndpoint()[0] === 500 && $copyRows === $before,'Allocation collision must not overwrite existing data');
resetDuplicateFixture(); $copyMethod = 'OPTIONS'; $before = $copyRows;
duplicateAssert(invokeDuplicateEndpoint()[0] === 204 && $copyRows === $before,'Preflight must not create');
resetDuplicateFixture(); $copyMethod = 'GET'; duplicateAssert(invokeDuplicateEndpoint()[0] === 405,'Method check');
// Legacy multiple-copy return shape and configuration filtering still work.
resetDuplicateFixture(); $copies = duplicateItem(8,'1',7,2);
duplicateAssert($copies === array(6,7),'Legacy multiple-copy contract');
duplicateAssert(!isset($copyRows['rs_property_text']['7:8:6:103']),'Legacy exclusion');
echo "duplicate item behavioral tests passed\n";
