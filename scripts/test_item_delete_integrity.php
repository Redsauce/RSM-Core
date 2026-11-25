<?php
require_once __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMqueryText.php';
// Execute the production functions against an isolated SQLite database. Inject
// failures after earlier writes, including metadata failures, to verify rollback.
function integrityExtract($source, $name)
{
    $start = strpos($source, 'function ' . $name . '(');
    if ($start === false) { throw new RuntimeException('Missing function ' . $name); }
    $body = ''; $depth = 0; $started = false;
    foreach (token_get_all('<?php ' . substr($source, $start)) as $token) {
        if (is_array($token) && $token[0] === T_OPEN_TAG) { continue; }
        $body .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $started = true; }
        elseif ($token === '}') { $depth--; }
        if ($started && $depth === 0) { break; }
    }
    return $body;
}
function integrityAssert($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
}
class IntegrityRows
{
    public $num_rows;
    private $rows = array();
    public function __construct($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $this->rows[] = $row; }
        $this->num_rows = count($this->rows);
    }
    public function fetch_assoc() { return array_shift($this->rows); }
}
class IntegrityConnection
{
    public $db, $errno = 0, $sqlstate = '00000', $error = '';
    public $fail = '', $queries = array(), $begins = 0, $commits = 0, $rollbacks = 0, $advanceCounter = false;
    public function __construct() {
        $this->db = new SQLite3(':memory:');
        $this->db->enableExceptions(true);
        $this->db->createFunction('GREATEST', function ($a, $b) { return max($a, $b); }, 2);
    }
    public function begin_transaction() { $this->begins++; return $this->db->exec('BEGIN'); }
    public function commit() {
        if ($this->fail === 'commit') { return false; }
        $this->commits++; return $this->db->exec('COMMIT');
    }
    public function rollback() { $this->rollbacks++; return $this->db->exec('ROLLBACK'); }
    public function query($sql) {
        $this->queries[] = $sql;
        if ($this->fail !== '' && strpos($sql, $this->fail) !== false) {
            throw new mysqli_sql_exception('Injected failure');
        }
        if ($this->advanceCounter && strpos($sql, 'UPDATE rs_item_types SET RS_LAST_ITEM_ID') === 0) {
            $this->db->exec('UPDATE rs_item_types SET RS_LAST_ITEM_ID=9');
        }
        $sql = str_replace(' FOR UPDATE', '', $sql);
        try {
            $this->errno = 0;
            return strpos($sql, 'SELECT ') === 0
                ? new IntegrityRows($this->db->query($sql)) : $this->db->exec($sql);
        } catch (Exception $e) {
            $this->errno = 1062;
            throw new mysqli_sql_exception('Injected database error');
        }
    }
}
$root = dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/utilities/';
require $root . 'RSMwriteTransactions.php';
eval(integrityExtract(file_get_contents($root . 'RSdatabase.php'), 'RSquery'));
$source = file_get_contents($root . 'RSMitemsManagement.php');
foreach (array('deleteItem', 'RSdeleteItemInTransaction', 'deleteItems', 'RSdeleteItemsInTransaction',
    'deleteClientProperty', 'RSdeleteClientPropertyInTransaction', 'deleteClientCategory', 'deleteClientItemType',
    'createEmptyItem', 'RSretryEmptyItemInsert', 'RScreateEmptyItemInTransaction', 'RSlockItemTypeForDuplication',
    'getClientItemTypeCategories', 'getClientCategoryProperties', 'getClientItemTypeProperties') as $name) {
    eval(integrityExtract($source, $name));
}
function RSerror($message) { }
function getClientItemTypes($client) { return array(); }
function getClientItemTypeID_RelatedWith_byName($name, $client) { return -1; }
function getClientPropertyItemType($property, $client) { return 50; }
function getClientPropertyType($property, $client) { return 'text'; }
function getMainPropertyID($type, $client) { return 100; }
function getNextItemTypeIdentification($type, $client) {
    global $mysqli;
    return 1 + $mysqli->db->querySingle('SELECT RS_LAST_ITEM_ID FROM rs_item_types WHERE RS_CLIENT_ID=7 AND RS_ITEMTYPE_ID=50');
}
function getNextIdentification($table, $column, $client, $filters) {
    global $mysqli;
    return 1 + $mysqli->db->querySingle('SELECT MAX(RS_ITEM_ID) FROM rs_items WHERE RS_CLIENT_ID=7 AND RS_ITEMTYPE_ID=50');
}
function deleteMediaFile(...$args) { }
function deleteMediaProperty(...$args) { }
function integrityReset() {
    global $mysqli, $propertiesTables, $definitions, $RSallowDebug, $queryCount, $cstRS_POST;
    global $RSMcreatedItemIDs, $RSMupdatedItemIDs, $RSMdeletedItemIDs;
    $mysqli = new IntegrityConnection();
    $RSallowDebug = false; $queryCount = 0; $cstRS_POST = 'request';
    $RSMcreatedItemIDs = $RSMupdatedItemIDs = $RSMdeletedItemIDs = array();
    $definitions = array('staff' => 'staff');
    $propertiesTables = array('text' => 'rs_property_text', 'file' => 'rs_property_files');
    $mysqli->db->exec("CREATE TABLE rs_items (RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ITEM_ID,PRIMARY KEY(RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ITEM_ID));
        INSERT INTO rs_items VALUES(7,50,1),(8,50,1);
        CREATE TABLE rs_item_types(RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_LAST_ITEM_ID,RS_MAIN_PROPERTY_ID);
        INSERT INTO rs_item_types VALUES(7,50,1,100);
        CREATE TABLE rs_categories(RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_CATEGORY_ID,RS_NAME,RS_ORDER);
        INSERT INTO rs_categories VALUES(7,50,10,'test',0);
        CREATE TABLE rs_item_properties(RS_CLIENT_ID,RS_PROPERTY_ID,RS_CATEGORY_ID,RS_NAME,RS_TYPE,RS_ORDER);
        INSERT INTO rs_item_properties VALUES(7,100,10,'test','text',0);");
    foreach (array('rs_property_text', 'rs_property_files') as $table) {
        $mysqli->db->exec('CREATE TABLE ' . $table . '(RS_CLIENT_ID,RS_ITEMTYPE_ID,RS_ITEM_ID,RS_PROPERTY_ID,RS_DATA)');
        // Property 999 has no definition, but must still be removed with its item.
        $mysqli->db->exec('INSERT INTO ' . $table . " VALUES(7,50,1,100,'value'),(7,50,1,999,'old value'),(8,50,1,100,'other client')");
    }
    foreach (array('rs_property_app_relations','rs_properties_groups','rs_properties_lists','rs_token_permissions') as $table) {
        $mysqli->db->exec('CREATE TABLE ' . $table . '(RS_CLIENT_ID,RS_PROPERTY_ID)');
        $mysqli->db->exec('INSERT INTO ' . $table . ' VALUES(7,100)');
    }
    $mysqli->db->exec('CREATE TABLE rs_item_type_app_relations(RS_CLIENT_ID,RS_ITEMTYPE_ID); INSERT INTO rs_item_type_app_relations VALUES(7,50)');
}
$operations = array(
    'single' => function () { deleteItem(50, 1, 7); },
    'batch' => function () { deleteItems(50, 7, '1'); },
    'property' => function () { deleteClientProperty(100, 7); },
    'category' => function () { deleteClientCategory(10, 7); },
    'type' => function () { deleteClientItemType(50, 7); },
);
$checks = 0;
foreach ($operations as $name => $operation) {
    foreach (array('DELETE FROM rs_property_files', 'commit') as $failure) {
        integrityReset(); $mysqli->fail = $failure;
        try { $operation(); throw new LogicException('Failure was ignored'); }
        catch (RuntimeException $e) { }
        integrityAssert($mysqli->rollbacks === 1 && $mysqli->commits === 0, $name . ': transaction not rolled back');
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items') === 2, $name . ': main row lost');
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_property_text') === 3, $name . ': values lost');
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_item_properties') === 1, $name . ': definition lost');
        $checks++;
    }
    integrityReset(); $operation();
    integrityAssert($mysqli->begins === 1 && $mysqli->commits === 1, $name . ': nested transaction committed independently');
    integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_property_text WHERE RS_CLIENT_ID=8') === 1, 'Other client modified');
    if ($name === 'single' || $name === 'batch' || $name === 'type') {
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items WHERE RS_CLIENT_ID=7') === 0, 'Main item survived successful deletion');
    } else {
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_item_properties') === 0, 'Definition survived successful deletion');
    }
    $checks++;
}
foreach (array('single', 'batch') as $name) {
    integrityReset(); $operations[$name]();
    integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_property_files WHERE RS_CLIENT_ID=7') === 0, 'Undefined property values survived');
    $checks++;
}
integrityReset(); $mysqli->fail = 'SELECT RS_PROPERTY_ID';
try { deleteClientCategory(10, 7); throw new LogicException('Metadata failure ignored'); } catch (RuntimeException $e) { }
integrityAssert($mysqli->rollbacks === 1, 'Metadata read failure did not roll back'); $checks++;
foreach (array('property' => 'DELETE FROM rs_token_permissions', 'category' => 'DELETE FROM rs_categories', 'type' => 'DELETE FROM rs_item_types') as $name => $failure) {
    integrityReset(); $mysqli->fail = $failure;
    try { $operations[$name](); throw new LogicException('Definition write failure ignored'); } catch (RuntimeException $e) { }
    integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_item_properties') === 1, 'Definition write failure lost metadata');
    integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_property_text') === 3, 'Definition write failure lost values');
    $checks++;
}
integrityReset();
$mysqli->db->exec("INSERT INTO rs_items VALUES(7,50,2); INSERT INTO rs_property_files VALUES(7,50,2,100,'second')");
try {
    RSwithItemWriteTransaction(function () use ($mysqli) {
        deleteItem(50, 1, 7);
        $mysqli->fail = 'DELETE FROM rs_property_files';
        deleteItem(50, 2, 7);
    });
    throw new LogicException('Second deletion failure ignored');
} catch (RuntimeException $e) { }
integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items') === 3 && $mysqli->commits === 0, 'Batch committed its first item before failure');
integrityAssert($RSMdeletedItemIDs === array(), 'Rolled-back deletion tracking survived'); $checks++;
integrityReset();
try {
    RSwithItemWriteTransaction(function () use ($mysqli) {
        deleteItem(50, 1, 7);
        integrityAssert($mysqli->begins === 1 && $mysqli->commits === 0, 'Caller transaction ownership lost');
        throw new RuntimeException('Caller cancels the complete group');
    });
} catch (RuntimeException $e) { }
integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items') === 2 && $mysqli->rollbacks === 1, 'Caller rollback failed'); $checks++;
integrityReset(); $mysqli->fail = 'UPDATE rs_item_types SET RS_LAST_ITEM_ID';
try { createEmptyItem(50, 7); throw new LogicException('Counter failure returned success'); } catch (RuntimeException $e) { }
integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items') === 2 && $mysqli->rollbacks === 1, 'Failed counter write left item behind'); $checks++;
integrityReset(); $mysqli->advanceCounter = true;
integrityAssert(createEmptyItem(50, 7) === 2 && $mysqli->db->querySingle('SELECT RS_LAST_ITEM_ID FROM rs_item_types') === 9, 'Counter moved backwards');
integrityAssert(strpos($mysqli->queries[0], 'FOR UPDATE') !== false, 'Legacy allocator did not lock the type'); $checks++;

// Exercise the actual tree command too: permission/relocation failures must not
// be reset to success, and all selected records share the same transaction.
class IntegrityResponse extends Exception {
    public $body;
    public function __construct($body) { $this->body = $body; parent::__construct('response'); }
}
function RSreturnArrayResults($body) { throw new IntegrityResponse($body); }
function getUserVisiblePropertiesIDs($type, $client, $user) { return $type == 50 ? array(100) : array(); }
function getDescendantsLevel($client, $type, $allowed) {
    return $GLOBALS['treeMode'] === 'move' ? array(array('propertyID' => 101, 'itemTypeID' => 51)) : array();
}
function isPropertyVisible($user, $property, $client) { return false; }
$tree = file_get_contents($root . '../genericTreeListBox/classGenericTreeListBox_deleteItems.php');
foreach (array('deleteDescendants', 'relocateDescendants') as $name) {
    $function = integrityExtract($tree, $name); eval($function); $tree = str_replace($function, '', $tree);
}
$tree = preg_replace('/^\s*(?:include_once|require_once)\s+[^;]+;/m', '', $tree);
foreach (array('recursive', 'move', 'move-success', 'write-failure', 'commit-failure', 'success') as $treeMode) {
    integrityReset(); $GLOBALS['treeMode'] = $treeMode;
    $cstClientID = 'clientID'; $cstItemTypeID = 'itemTypeID'; $RSuserID = 0;
    $_POST = $GLOBALS[$cstRS_POST] = array('clientID' => 7, 'itemTypeID' => 50, 'itemIDs' => '1');
    if ($treeMode === 'recursive') { $_POST['recursiveDelete'] = 1; $_POST['allowedItemPaths'] = '51,101'; }
    if ($treeMode === 'move' || $treeMode === 'move-success') { $_POST['newParentID'] = '5'; }
    $GLOBALS[$cstRS_POST] = $_POST; $results = array();
    unset($recursiveDelete, $newParentID, $allowedItemPaths);
    if ($treeMode === 'write-failure') { $mysqli->fail = 'DELETE FROM rs_property_files'; }
    if ($treeMode === 'commit-failure') { $mysqli->fail = 'commit'; }
    try { eval(substr($tree, 5)); throw new LogicException('Tree response missing'); }
    catch (IntegrityResponse $response) {
        $success = $treeMode === 'success' || $treeMode === 'move-success';
        integrityAssert($response->body['result'] === ($success ? 'OK' : 'NOK'), 'Wrong tree response: ' . $treeMode);
        integrityAssert($mysqli->db->querySingle('SELECT COUNT(*) FROM rs_items') === ($success ? 1 : 2), 'Tree lost records: ' . $treeMode);
        integrityAssert($mysqli->begins === 1 && $mysqli->commits === ($success ? 1 : 0), 'Tree transaction ownership failed');
        $checks++;
    }
}
echo 'PASS: ' . $checks . " atomic delete, metadata, caller transaction and allocator checks\n";
