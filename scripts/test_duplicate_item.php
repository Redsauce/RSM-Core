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
            if ($copyFail === 'child' && $row[1] === '9') return false;
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
    $properties = $GLOBALS['copyPropertiesByType'][$type] ?? $GLOBALS['copyProperties'];
    return array_values(array_filter($properties, function ($property) use ($exclude) { return !$exclude || !$property['excluded']; }));
}
function getMainPropertyID($type, $client) { return $GLOBALS['copyMain']; }
function handleApiCorsPreflight($method) { if ($GLOBALS['copyMethod'] === 'OPTIONS') throw new DuplicateResponse(204, null); }
function setAuthorizationTokenOnGlobals() { }
function checkCorrectRequestMethod($method) { if ($GLOBALS['copyMethod'] !== $method) throw new DuplicateResponse(405, null); }
function getRequestBody() { return $GLOBALS['copyBody']; }
function getRStoken() { return 'test'; }
function RSclientFromToken($token) { return 7; }
function getRSuserID() { return 3; }
function parseITID($type, $client) {
    if (in_array($type, array('8', 8, 'generic.type'), true)) return 8;
    if ($type === 'generic.child' && isset($GLOBALS['copyPropertiesByType'][9])) return 9;
    return isset($GLOBALS['copyPropertiesByType'][$type]) ? intval($type) : 0;
}
function getClientPropertyReferredItemType($property, $client) { return $GLOBALS['copyReferredTypes'][$property] ?? 0; }
function verifyItemExists($item, $type, $client) { return isset($GLOBALS['copyRows']['items']["$client:$type:$item"]); }
function RShasTokenPermission($token, $property, $permission) { return !in_array("$property:$permission", $GLOBALS['copyDenied'], true); }
function isPropertyVisible($user, $property, $client) { return $GLOBALS['copyVisible']; }
function RSisCustomerScopedToken($token) { return $GLOBALS['copyScoped']; }
function RSgetTokenCustomerDependencyPropertyID($token, $type, $client) { return 104; }
function RSitemMatchesTokenCustomerScope($token, $client, $type, $item) {
    if (in_array("$type:$item", $GLOBALS['copyScopeDenied'], true)) return false;
    return !$GLOBALS['copyScoped'] || (($GLOBALS['copyRows']['rs_property_identifiers']["$client:$type:$item:104"]['RS_DATA'] ?? '') === '99');
}
function RSError($message) { $GLOBALS['copyRows']['errors'][] = $message; }
function returnJsonMessage($code, $message) { throw new DuplicateResponse($code, $message); }
function returnJsonResponse($json) { throw new DuplicateResponse(200, json_decode($json, true)); }

$root = dirname(__DIR__);
$manager = file_get_contents($root . '/Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
foreach (array('duplicateItem', 'RSlockItemTypeForDuplication', 'replaceIdentifier', 'in_array_recursive') as $name) eval(duplicateExtractFunction($manager, $name));
$copyEndpoint = file_get_contents($root . '/Server/htdocs/AppController/commands_RSM/api/v2/items/duplicate.php');
$copyEndpoint = preg_replace('/^require_once .*;\R/m', '', substr($copyEndpoint, 5));
$propertiesTables = array('text'=>'rs_property_text', 'integer'=>'rs_property_integers', 'date'=>'rs_property_dates', 'identifier'=>'rs_property_identifiers', 'identifiers'=>'rs_property_multiIdentifiers', 'file'=>'rs_property_files', 'image'=>'rs_property_images');
function resetDuplicateFixture() {
    global $copyRows, $copyProperties, $copyBody, $copyMethod, $copyFail, $copyDenied, $copyScoped, $copyVisible, $copyMain, $mysqli, $RSallowDebug;
    $mysqli = new DuplicateMemoryDb();
    $RSallowDebug = false;
    $GLOBALS['copyPropertiesByType'] = array();
    $GLOBALS['copyReferredTypes'] = array();
    $GLOBALS['copyScopeDenied'] = array();
    $GLOBALS['copyRecursiveProperties'] = array();
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
    try { eval($GLOBALS['copyEndpoint']); } catch (DuplicateResponse $response) {
        $GLOBALS['copyErrorLog'] = $GLOBALS['copyRows']['errors'] ?? array();
        unset($GLOBALS['copyRows']['errors']);
        return array($response->getCode(), $response->payload);
    }
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

foreach (array(null, array(), (object)array(), (object)array('itemTypeID'=>'8','itemID'=>0), (object)array('itemTypeID'=>'8','itemID'=>'1,2'), (object)array('itemTypeID'=>'8','itemID'=>true), (object)array('itemTypeID'=>'8','itemID'=>'99999999999999999999999'), (object)array('itemTypeID'=>'8','itemID'=>1,'numCopies'=>2), (object)array('itemTypeID'=>array(),'itemID'=>1)) as $invalid) {
    resetDuplicateFixture(); $copyBody = $invalid; $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 400 && $copyRows === $before, 'Invalid request must not write');
}
resetDuplicateFixture(); $copyBody->itemTypeID = 'unknown'; duplicateAssert(invokeDuplicateEndpoint()[0] === 400,'Unknown type');
resetDuplicateFixture(); $copyBody->itemID = 999; duplicateAssert(invokeDuplicateEndpoint()[0] === 404,'Missing source');
resetDuplicateFixture(); $copyBody->itemTypeID = 'generic.type'; duplicateAssert(invokeDuplicateEndpoint()[0] === 200,'Mapped type');
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
function getRecursivePropertyID($type, $client) { return $GLOBALS['copyRecursiveProperties'][$type] ?? 0; }
function array_search_ID($id, $rows, $column) {
    foreach ($rows as $key=>$row) if ($row[$column] == $id) return $key;
    return false;
}
function getPropertyType($id, $client) {
    foreach (array_merge(array($GLOBALS['copyProperties']), array_values($GLOBALS['copyPropertiesByType'])) as $properties) {
        foreach ($properties as $property) if ($property['id'] == $id) return $property['type'];
    }
    return '';
}
function getFilteredItemsIDs($type, $client, $filters, $returns) {
    if ($GLOBALS['copyFail'] === 'discovery') throw new RuntimeException('Dependency query failed');
    $property = $filters[0]['ID'];
    $parents = explode(',', (string)$filters[0]['value']);
    $table = $GLOBALS['propertiesTables'][getPropertyType($property, $client)];
    $rows = array();
    foreach ($GLOBALS['copyRows'][$table] ?? array() as $key=>$row) {
        list($rowClient,$rowType,$item,$rowProperty) = explode(':', $key);
        if ($rowClient == $client && $rowType == $type && $rowProperty == $property
            && isset($GLOBALS['copyRows']['items']["$client:$type:$item"])
            && count(array_intersect($parents, explode(',', (string)$row['RS_DATA']))) > 0) {
            $rows[] = array('ID'=>$item,'parent'=>$row['RS_DATA']);
        }
    }
    return $rows;
}
function getItemPropertyValue($item, $property, $client, $type, $itemTypeID = null) {
    $table = $GLOBALS['propertiesTables'][$type];
    foreach ($GLOBALS['copyRows'][$table] ?? array() as $key=>$row) {
        list($rowClient,$rowType,$rowItem,$rowProperty) = explode(':', $key);
        if ($rowClient == $client && $rowItem == $item && $rowProperty == $property && ($itemTypeID === null || $rowType == $itemTypeID)) return $row['RS_DATA'];
    }
    return '';
}
function setPropertyValueByID($property, $type, $item, $client, $value, $propertyType = '', $user = 0) {
    if ($GLOBALS['copyFail'] === 'remap') return -3;
    $table = $GLOBALS['propertiesTables'][getPropertyType($property, $client)];
    $GLOBALS['copyRows'][$table]["$client:$type:$item:$property"]['RS_DATA'] = (string)$value;
    return 0;
}

function resetDescendantFixture() {
    resetDuplicateFixture();
    $GLOBALS['copyPropertiesByType'] = array(
        9 => array(array('id'=>201,'type'=>'identifier','excluded'=>false), array('id'=>202,'type'=>'text','excluded'=>false)),
        10 => array(array('id'=>301,'type'=>'identifiers','excluded'=>false), array('id'=>302,'type'=>'identifier','excluded'=>false)));
    $GLOBALS['copyReferredTypes'] = array(201=>8, 301=>9, 302=>8);
    $GLOBALS['copyBody']->descendants = array(
        (object)array('itemTypeID'=>10,'dependencyPropertyID'=>301),
        (object)array('itemTypeID'=>9,'dependencyPropertyID'=>201));
    foreach (array('7:9:2', '7:9:3', '7:10:1', '9:9:1') as $key) $GLOBALS['copyRows']['items'][$key] = true;
    foreach (array('7:9:1:201'=>'1', '7:9:2:201'=>'1', '7:9:3:201'=>'99', '9:9:1:201'=>'1', '7:10:1:302'=>'1') as $key=>$value) {
        $GLOBALS['copyRows']['rs_property_identifiers'][$key] = array('RS_DATA'=>$value, 'RS_ORDER'=>'4');
    }
    $GLOBALS['copyRows']['rs_property_multiIdentifiers']['7:10:1:301'] = array('RS_DATA'=>'1', 'RS_ORDER'=>'9');
    $GLOBALS['copyRows']['rs_property_text']['7:9:1:202'] = array('RS_DATA'=>'Concepto ñ');
}

resetDescendantFixture(); $before = $copyRows;
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200, 'Descendant copy succeeds');
duplicateAssert($response[1]['copiedItems'] === array(
    array('itemTypeID'=>8,'sourceItemID'=>1,'newItemID'=>6),
    array('itemTypeID'=>9,'sourceItemID'=>1,'newItemID'=>7),
    array('itemTypeID'=>9,'sourceItemID'=>2,'newItemID'=>8),
    array('itemTypeID'=>10,'sourceItemID'=>1,'newItemID'=>9)), 'Chains and shared children are copied once, regardless of edge order');
duplicateAssert($copyRows['rs_property_identifiers']['7:9:7:201']['RS_DATA'] === '6', 'Concept points to new invoice');
duplicateAssert($copyRows['rs_property_multiIdentifiers']['7:10:9:301']['RS_DATA'] === '7', 'Shared relation remaps copied parents and preserves external references');
duplicateAssert($copyRows['rs_property_identifiers']['7:10:9:302']['RS_DATA'] === '1', 'Unselected relation remains unchanged');
duplicateAssert(count($copyRows['items']) === count($before['items']) + 4, 'No unrelated or foreign items copied');
foreach ($before as $table=>$rows) {
    if ($table === 'counter') continue;
    foreach ($rows as $key=>$row) duplicateAssert($copyRows[$table][$key] === $row, 'Graph copy must leave originals unchanged');
}

resetDescendantFixture();
$copyBody->descendants[] = (object)array('itemTypeID'=>10,'dependencyPropertyID'=>302);
$copyBody->descendants[] = (object)array('itemTypeID'=>9,'dependencyPropertyID'=>201);
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200 && count($response[1]['copiedItems']) === 4, 'Diamond and duplicate edges do not duplicate nodes');
$bySource = array();
foreach ($response[1]['copiedItems'] as $copy) $bySource[$copy['itemTypeID'].':'.$copy['sourceItemID']] = $copy['newItemID'];
duplicateAssert($copyRows['rs_property_identifiers']['7:10:'.$bySource['10:1'].':302']['RS_DATA'] === '6', 'Additional selected relation is remapped');

resetDescendantFixture();
$copyProperties[] = array('id'=>111,'type'=>'identifier','excluded'=>false);
$copyReferredTypes[111] = 10;
$copyRows['rs_property_identifiers']['7:8:1:111'] = array('RS_DATA'=>'1','RS_ORDER'=>'0');
$copyBody->descendants[] = (object)array('itemTypeID'=>8,'dependencyPropertyID'=>111);
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200 && count($response[1]['copiedItems']) === 4, 'Cycle terminates and copies root once');
duplicateAssert($copyRows['rs_property_identifiers']['7:8:6:111']['RS_DATA'] === '9', 'Cycle points to copied descendant');

resetDescendantFixture();
$copyPropertiesByType[9][] = array('id'=>203,'type'=>'identifier','excluded'=>false);
$copyReferredTypes[203] = 9;
$copyRows['rs_property_identifiers']['7:9:3:203'] = array('RS_DATA'=>'1','RS_ORDER'=>'0');
$copyBody->descendants[] = (object)array('itemTypeID'=>'generic.child','dependencyPropertyID'=>203);
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200 && count($response[1]['copiedItems']) === 5, 'Explicit recursive relation reaches another level using mapped child type');
$bySource = array();
foreach ($response[1]['copiedItems'] as $copy) $bySource[$copy['itemTypeID'].':'.$copy['sourceItemID']] = $copy['newItemID'];
duplicateAssert($copyRows['rs_property_identifiers']['7:9:'.$bySource['9:3'].':203']['RS_DATA'] === (string)$bySource['9:1'], 'Recursive child points to copied parent');
duplicateAssert($copyRows['rs_property_multiIdentifiers']['7:10:'.$bySource['10:1'].':301']['RS_DATA'] === (string)$bySource['9:1'], 'References remap even when another parent is discovered later');

resetDescendantFixture(); $copyBody->descendants = array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>201));
$copyRows['rs_property_identifiers']['7:9:1:201']['RS_DATA'] = '99';
$copyRows['rs_property_identifiers']['7:9:2:201']['RS_DATA'] = '99';
duplicateAssert(count(invokeDuplicateEndpoint()[1]['copiedItems']) === 1, 'Selected type without matching children copies only root');

resetDescendantFixture();
$copyRecursiveProperties[9] = 203;
$before = $copyRows;
duplicateAssert(invokeDuplicateEndpoint()[0] === 400 && $copyRows === $before, 'Implicit recursive relation cannot bypass configured exclusions');

resetDescendantFixture(); $copyBody->descendants = array();
duplicateAssert(count(invokeDuplicateEndpoint()[1]['copiedItems']) === 1, 'Empty descendants copies only root');
resetDescendantFixture(); unset($copyBody->descendants);
duplicateAssert(!isset(invokeDuplicateEndpoint()[1]['copiedItems']), 'Omitted descendants retains original response');

foreach (array(null, (object)array(), '9', array(9), array((object)array('itemTypeID'=>9)),
    array((object)array('itemTypeID'=>array(),'dependencyPropertyID'=>201)),
    array((object)array('itemTypeID'=>'unknown','dependencyPropertyID'=>201)),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>true)),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>0)),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>'9999999999999999999999999')),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>201,'extra'=>1)),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>202)),
    array((object)array('itemTypeID'=>9,'dependencyPropertyID'=>104)),
    array((object)array('itemTypeID'=>10,'dependencyPropertyID'=>301))) as $invalid) {
    resetDescendantFixture(); $copyBody->descendants = $invalid; $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 400 && $copyRows === $before, 'Malformed, foreign, non-relation or disconnected dependency rejected');
}
resetDescendantFixture(); $copyPropertiesByType[9][0]['excluded'] = true; $before = $copyRows;
duplicateAssert(invokeDuplicateEndpoint()[0] === 400 && $copyRows === $before, 'Excluded dependency cannot be forced into a copy');
foreach (array('READ','CREATE') as $permission) {
    resetDescendantFixture(); $copyDenied = array('202:'.$permission); $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 403 && $copyRows === $before, 'Descendant permission failure rejects entire graph');
}
foreach (array('9:1', '10:9') as $denied) {
    resetDescendantFixture(); $copyScopeDenied = array($denied); $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 403 && $copyRows === $before, 'Source and destination descendant scope enforced');
}
foreach (array('discovery','child','remap','commit') as $failure) {
    resetDescendantFixture(); $copyFail = $failure; $before = $copyRows;
    duplicateAssert(invokeDuplicateEndpoint()[0] === 500 && $copyRows === $before && $mysqli->rollbacks === 1, 'Graph read/write failure rolls back all copies');
}
resetDescendantFixture(); $copyFail = 'remap'; $RSallowDebug = true;
$response = invokeDuplicateEndpoint();
duplicateAssert($response === array(500, 'Unable to duplicate item (copying item and descendants)'), 'Debug response identifies the failing phase');
duplicateAssert(count($copyErrorLog) === 1 && strpos($copyErrorLog[0], 'Copy failed') !== false, 'Exception diagnostic survives transaction rollback');
resetDuplicateFixture(); $copyFail = 'metadata';
duplicateAssert(invokeDuplicateEndpoint() === array(500, ''), 'Production response does not expose internal details');
duplicateAssert(strpos($copyErrorLog[0], 'reading properties of item type 8: RuntimeException: Metadata read failed') !== false, 'Persistent log records context and original exception');
// Shared children retain their identity; every original parent stays attached.
resetDescendantFixture();
$copyRows['rs_property_multiIdentifiers']['7:10:1:301'] = array('RS_DATA'=>'1,2,3','RS_ORDER'=>'9,4,8');
$before = $copyRows;
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200 && count($response[1]['copiedItems']) === 3, 'Shared child is not copied');
duplicateAssert($copyRows['rs_property_multiIdentifiers']['7:10:1:301']['RS_DATA'] === '1,2,3,7,8', 'Shared child retains old parents and adds each copied parent once');
duplicateAssert(count($copyRows['items']) === count($before['items']) + 3, 'Only root and exclusive children are created');
duplicateAssert($copyRows['rs_property_identifiers']['7:10:1:302'] === $before['rs_property_identifiers']['7:10:1:302'], 'Other shared-child properties stay unchanged');

function resetSharedChildFixture() {
    resetDescendantFixture();
    $GLOBALS['copyPropertiesByType'][9][0]['type'] = 'identifiers';
    $GLOBALS['copyRows']['rs_property_multiIdentifiers']['7:9:1:201'] = array('RS_DATA'=>'1,99','RS_ORDER'=>'0,1');
}
resetSharedChildFixture(); $before = $copyRows;
$response = invokeDuplicateEndpoint();
duplicateAssert($response[0] === 200 && count($response[1]['copiedItems']) === 1, 'One shared child does not cause its descendants to be copied');
duplicateAssert($copyRows['rs_property_multiIdentifiers']['7:9:1:201']['RS_DATA'] === '1,99,6', 'Original shared child gains the new root');
duplicateAssert($copyRows['rs_property_multiIdentifiers']['7:10:1:301'] === $before['rs_property_multiIdentifiers']['7:10:1:301'], 'Shared child subtree remains unchanged');
foreach (array('permission', 'scope', 'remap', 'commit') as $failure) {
    resetSharedChildFixture(); $before = $copyRows;
    if ($failure === 'permission') $copyDenied = array('201:WRITE');
    elseif ($failure === 'scope') $copyScopeDenied = array('9:1');
    else $copyFail = $failure;
    $response = invokeDuplicateEndpoint();
    duplicateAssert($response[0] === (in_array($failure, array('permission','scope')) ? 403 : 500) && $copyRows === $before, 'Shared-child failure rolls back both new parent and existing relation changes');
}
resetSharedChildFixture();
$copied = array(); $metadata = array();
$copies = duplicateItem(8, 1, 7, 2, array(8=>array(array(9,201))), $copied, $metadata);
duplicateAssert($copies === array(6,7) && $copyRows['rs_property_multiIdentifiers']['7:9:1:201']['RS_DATA'] === '1,99,6,7', 'Legacy multiple-copy call reuses shared child for all new parents');
duplicateAssert(!isset($copied[9]), 'Reused children are not reported as copies');
echo "duplicate item behavioral tests passed\n";
