<?php

// MariaDB differential tests for dynamic item joins.
// Creates a disposable local database, fills it with deterministic random data,
// then compares legacy HEAD behavior with the optimized working-tree behavior.
// Benchmark local orientativo; no define valores por entorno.

$root = dirname(__DIR__);
// RSM_BENCH_CURRENT_PATH permite comparar el codigo actual contra una copia temporal.
// Lo usamos para medir el ahorro de cache sin mezclarlo con otros cambios legacy.
$currentPath = getenv('RSM_BENCH_CURRENT_PATH') ?: $root . '/Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php';
$currentSource = file_get_contents($currentPath);
if ($currentSource === false) {
    fwrite(STDERR, "Unable to read current RSMitemsManagement.php\n");
    exit(1);
}

function diffAssert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

function extractFunctionSourceForDbDiff($source, $functionName) {
    $needle = 'function ' . $functionName . '(';
    $start = strpos($source, $needle);
    diffAssert($start !== false, $functionName . ' must exist');
    $brace = strpos($source, '{', $start);
    diffAssert($brace !== false, $functionName . ' must have a body');
    $depth = 0;
    $length = strlen($source);
    for ($i = $brace; $i < $length; $i++) {
        if ($source[$i] === '{') $depth++;
        if ($source[$i] === '}') $depth--;
        if ($depth === 0) return substr($source, $start, $i - $start + 1);
    }
    diffAssert(false, $functionName . ' body must close');
}

function normalizeRows($rows) {
    if (is_string($rows) && file_exists($rows)) {
        $xml = simplexml_load_file($rows, 'SimpleXMLElement', LIBXML_NOCDATA);
        @unlink($rows);
        $out = array();
        if ($xml !== false && isset($xml->rows->row)) {
            foreach ($xml->rows->row as $row) {
                $item = array();
                foreach ($row->column as $column) {
                    $item[(string) $column['name']] = (string) $column;
                }
                $out[] = $item;
            }
        }
        $rows = $out;
    }

    $normalized = array();
    foreach ($rows as $row) {
        $copy = array();
        foreach ($row as $key => $value) {
            $copy[(string) $key] = is_null($value) ? '' : (string) $value;
        }
        ksort($copy);
        $normalized[] = $copy;
    }
    return $normalized;
}

function benchmarkCall($callback, $iterations) {
    global $queryCount;
    $times = array();
    $queries = array();
    $lastRows = null;
    for ($i = 0; $i < $iterations; $i++) {
        $queryCount = 0;
        $start = microtime(true);
        $rows = $callback();
        $elapsed = (microtime(true) - $start) * 1000;
        $times[] = $elapsed;
        $queries[] = $queryCount;
        $lastRows = normalizeRows($rows);
    }
    sort($times);
    sort($queries);
    return array(
        'rows' => $lastRows,
        'medianMs' => $times[(int) floor(count($times) / 2)],
        'minMs' => $times[0],
        'maxMs' => $times[count($times) - 1],
        'queriesMedian' => $queries[(int) floor(count($queries) / 2)],
    );
}

function benchmarkScenario($name, $filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile, $iterations) {
    $legacy = benchmarkCall(function() use ($filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile) {
        return Legacy_getFilteredItemsIDs(5, 7, $filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile, '', false);
    }, $iterations);

    $current = benchmarkCall(function() use ($filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile) {
        return getFilteredItemsIDs(5, 7, $filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile, '', false);
    }, $iterations);

    if ($legacy['rows'] !== $current['rows']) {
        fwrite(STDERR, "FAIL: scenario {$name} produced different responses\n");
        exit(1);
    }

    $delta = $current['medianMs'] - $legacy['medianMs'];
    $speedup = $legacy['medianMs'] > 0 ? (($legacy['medianMs'] - $current['medianMs']) / $legacy['medianMs']) * 100 : 0;
    printf(
        "%-24s rows=%4d legacy=%8.2fms current=%8.2fms delta=%8.2fms speedup=%7.1f%% queries=%d->%d\n",
        $name,
        count($current['rows']),
        $legacy['medianMs'],
        $current['medianMs'],
        $delta,
        $speedup,
        $legacy['queriesMedian'],
        $current['queriesMedian']
    );
}

$mysqli = @new mysqli('127.0.0.1', 'root', '', 'mysql', 3306);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Local MariaDB is not reachable as root without password: " . $mysqli->connect_error . "\n");
    exit(2);
}
$mysqli->set_charset('utf8mb4');
$dbName = 'rsm_dynamic_join_diff';
$mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
$mysqli->query('CREATE DATABASE `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$mysqli->select_db($dbName);

$schema = array(
    'CREATE TABLE rs_items (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_ITEM_ID INT NOT NULL, RS_ORDER INT DEFAULT 0, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_item_types (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_NAME VARCHAR(100), RS_MAIN_PROPERTY_ID INT NOT NULL DEFAULT 0, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_item_properties (RS_CLIENT_ID INT NOT NULL, RS_PROPERTY_ID INT NOT NULL, RS_CATEGORY_ID INT DEFAULT 1, RS_NAME VARCHAR(100), RS_TYPE VARCHAR(40), RS_DEFAULTVALUE TEXT, RS_REFERRED_ITEMTYPE INT DEFAULT 0, PRIMARY KEY (RS_CLIENT_ID, RS_PROPERTY_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_property_text (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_ITEM_ID INT NOT NULL, RS_PROPERTY_ID INT NOT NULL, RS_DATA TEXT, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_property_identifiers (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_ITEM_ID INT NOT NULL, RS_PROPERTY_ID INT NOT NULL, RS_DATA TEXT, RS_ORDER TEXT NULL, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID), KEY identifier (RS_CLIENT_ID, RS_PROPERTY_ID, RS_DATA(32))) ENGINE=InnoDB',
    'CREATE TABLE rs_property_multiIdentifiers (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_ITEM_ID INT NOT NULL, RS_PROPERTY_ID INT NOT NULL, RS_DATA TEXT, RS_ORDER TEXT NULL, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_property_files (RS_CLIENT_ID INT NOT NULL, RS_ITEMTYPE_ID INT NOT NULL, RS_ITEM_ID INT NOT NULL, RS_PROPERTY_ID INT NOT NULL, RS_NAME VARCHAR(255), RS_SIZE INT, RS_DATA LONGBLOB, PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID)) ENGINE=InnoDB',
    'CREATE TABLE rs_property_images LIKE rs_property_files',
);
foreach ($schema as $sql) $mysqli->query($sql);

$mysqli->query("INSERT INTO rs_item_types VALUES (7,5,'things',10),(7,99,'owners',20)");
$mysqli->query("INSERT INTO rs_item_properties VALUES (7,10,1,'name','text','',0),(7,11,1,'status','text','active',0),(7,12,1,'bucket','text','',0),(7,20,1,'ownerName','text','',0),(7,30,1,'owner','identifier','0',99)");

$items = array();
$names = array();
$statuses = array();
$buckets = array();
$owners = array();
for ($i = 1; $i <= 50; $i++) {
    $items[] = "(7,99,{$i},{$i})";
    $ownerName = sprintf('Owner %02d', $i);
    $names[] = "(7,99,{$i},20,'" . $mysqli->real_escape_string($ownerName) . "')";
}
for ($i = 1; $i <= 1500; $i++) {
    $status = ($i % 3 === 0) ? 'archived' : (($i % 2 === 0) ? 'active' : 'pending');
    $bucket = 'bucket-' . ($i % 17);
    $owner = ($i % 50) + 1;
    $name = 'Item ' . str_pad((string) $i, 4, '0', STR_PAD_LEFT) . ' ' . substr(md5((string) $i), 0, 8);
    $items[] = "(7,5,{$i}," . (1501 - $i) . ")";
    $names[] = "(7,5,{$i},10,'" . $mysqli->real_escape_string($name) . "')";
    if ($i % 11 !== 0) $statuses[] = "(7,5,{$i},11,'{$status}')";
    if ($i % 7 !== 0) $buckets[] = "(7,5,{$i},12,'{$bucket}')";
    $owners[] = "(7,5,{$i},30,'{$owner}','{$i}')";
}
foreach (array_chunk($items, 300) as $chunk) $mysqli->query('INSERT INTO rs_items VALUES ' . implode(',', $chunk));
foreach (array_chunk($names, 300) as $chunk) $mysqli->query('INSERT INTO rs_property_text VALUES ' . implode(',', $chunk));
foreach (array_chunk($statuses, 300) as $chunk) $mysqli->query('INSERT INTO rs_property_text VALUES ' . implode(',', $chunk));
foreach (array_chunk($buckets, 300) as $chunk) $mysqli->query('INSERT INTO rs_property_text VALUES ' . implode(',', $chunk));
foreach (array_chunk($owners, 300) as $chunk) $mysqli->query('INSERT INTO rs_property_identifiers VALUES ' . implode(',', $chunk));

echo "Fixture loaded: 1500 items, 50 owner items\n";

$propertiesTables = array(
    'text' => 'rs_property_text',
    'identifier' => 'rs_property_identifiers',
    'identifiers' => 'rs_property_multiIdentifiers',
    'file' => 'rs_property_files',
    'image' => 'rs_property_images',
);
$RStempPath = sys_get_temp_dir();
$cstCDATAseparator = ']]]]><![CDATA[>';
$cstMainPropertyID = 'mainPropertyID';
$cstMainPropertyType = 'mainPropertyType';
$cstReferredItemTypeID = 'referredItemTypeID';
$cstUTF8 = 'UTF-8';
$queryCount = 0;
$RSallowDebug = false;
$cstRS_POST = 'RS_POST';
$GLOBALS[$cstRS_POST] = array();

function RSQuery($query, $registerError = true) {
    global $mysqli, $queryCount;
    $queryCount++;
    return $mysqli->query($query);
}
function RSerror($message) { fwrite(STDERR, $message . "\n"); }
function parsePID($propertyID, $clientID) { return $propertyID; }
function parseITID($itemTypeID, $clientID) { return $itemTypeID; }
function getPropertyType($propertyID, $clientID) {
    $result = RSQuery('SELECT RS_TYPE FROM rs_item_properties WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_PROPERTY_ID = ' . intval($propertyID));
    $row = $result->fetch_assoc();
    return $row ? $row['RS_TYPE'] : '';
}
function getClientPropertyDefaultValue($propertyID, $clientID) {
    $result = RSQuery('SELECT RS_DEFAULTVALUE FROM rs_item_properties WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_PROPERTY_ID = ' . intval($propertyID));
    $row = $result->fetch_assoc();
    return $row ? $row['RS_DEFAULTVALUE'] : '';
}
function getMainPropertyID($itemTypeID, $clientID) {
    $result = RSQuery('SELECT RS_MAIN_PROPERTY_ID FROM rs_item_types WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_ITEMTYPE_ID = ' . intval($itemTypeID));
    $row = $result->fetch_assoc();
    return $row ? $row['RS_MAIN_PROPERTY_ID'] : 0;
}
function getClientPropertyReferredItemType($propertyID, $clientID) {
    $result = RSQuery('SELECT RS_REFERRED_ITEMTYPE FROM rs_item_properties WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_PROPERTY_ID = ' . intval($propertyID));
    $row = $result->fetch_assoc();
    return $row ? $row['RS_REFERRED_ITEMTYPE'] : 0;
}
function isIdentifier($propertyID, $clientID, $typeName = '') { return $typeName == 'identifier' || $typeName == 'identifiers'; }
function isSingleIdentifier($propertyType) { return $propertyType == 'identifier'; }
function isMultiIdentifier($propertyType) { return $propertyType == 'identifiers'; }
function convertData($fieldName, $fieldType) { return $fieldName; }
function applyExternalFilters($itemTypeID, $clientID, $results, $extFilterRules) { return $results; }
function getItemTypeIDFromProperties($propertiesID, $clientID) { return 5; }
function getTreePath($clientID, &$treePath, $root, $itemTypeID, $allowedItemTypes, $level) { $treePath = array(); }
function getPathsForItem($clientID, $itemTypeID, $itemID, $treePath, $level, $path) { return array(); }
function mysqlToXML($resource, $clientID, $itemTypeID, $propertiesToTranslate = array(), $extFilterRules = '', $decodeEntities = false) {
    global $RStempPath, $cstCDATAseparator;
    $filename = tempnam($RStempPath, 'RSL');
    $writer = new XMLWriter();
    $writer->openUri($filename);
    $writer->startDocument('1.0', 'UTF-8');
    $writer->startElement('RSRecordset');
    $writer->startElement('rows');
    while ($row = $resource->fetch_assoc()) {
        $writer->startElement('row');
        foreach ($row as $field => $value) {
            $writer->startElement('column');
            $writer->writeAttribute('name', $field);
            $writer->writeCData(str_replace(']]>', $cstCDATAseparator, is_null($value) ? '' : $value));
            $writer->endElement();
        }
        $writer->endElement();
    }
    $writer->endElement();
    $writer->endElement();
    $writer->endDocument();
    return $filename;
}
function getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType = '', $itemTypeID = '') {
    global $propertiesTables;
    if ($itemTypeID == '') $itemTypeID = ($propertyID == 20 ? 99 : 5);
    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);
    $result = RSQuery('SELECT RS_DATA AS DATA FROM ' . $propertiesTables[$propertyType] . ' WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_ITEMTYPE_ID = ' . intval($itemTypeID) . ' AND RS_ITEM_ID = ' . intval($itemID) . ' AND RS_PROPERTY_ID = ' . intval($propertyID));
    $row = $result->fetch_assoc();
    return $row ? $row['DATA'] : '';
}
function getItemsPropertyValues($propertyID, $clientID, $itemIDs = '', $propertyType = '', $itemTypeID = '', $translateIds = false, $returnOrder = 0, &$orderArray = array()) {
    global $propertiesTables;
    if ($itemTypeID == '') $itemTypeID = ($propertyID == 20 ? 99 : 5);
    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);
    $positionSelect = $returnOrder && ($propertyType == 'identifier' || $propertyType == 'identifiers') ? ', RS_ORDER AS DATA_ord' : '';
    $sql = 'SELECT RS_ITEM_ID AS ID, RS_DATA AS DATA' . $positionSelect . ' FROM ' . $propertiesTables[$propertyType] . ' WHERE RS_CLIENT_ID = ' . intval($clientID) . ' AND RS_ITEMTYPE_ID = ' . intval($itemTypeID) . ' AND RS_PROPERTY_ID = ' . intval($propertyID);
    if ($itemIDs != '') $sql .= ' AND RS_ITEM_ID IN (' . $itemIDs . ')';
    $result = RSQuery($sql);
    $out = array();
    while ($row = $result->fetch_assoc()) {
        $out[$row['ID']] = $row['DATA'];
        if (isset($row['DATA_ord'])) $orderArray[$row['ID']] = $row['DATA_ord'];
    }
    return $out;
}
function getTranslatedValue($clientID, $property, $sourceValue) {
    global $cstMainPropertyID, $cstMainPropertyType, $cstReferredItemTypeID;
    if ($property['type'] == 'identifier') {
        return getItemPropertyValue($sourceValue, $property[$cstMainPropertyID], $clientID, $property[$cstMainPropertyType], $property[$cstReferredItemTypeID]);
    }
    return $sourceValue;
}

$currentFunctions = array(
    'IQ_getItems',
    '_getFilterClause',
    '_getTranslatedFilterClause',
    '_getTranslatedFilterSubquery',
    '_translateIds',
    'RSMgetFilteredPropertyType',
    'RSMgetFilteredPropertyDefaultValue',
    'RSMgetFilteredMainPropertyID',
    'IQ_getFilteredItemIDsOnly',
    'RShydrateFilteredItemsProperties',
    'RSfilteredItemsArrayToXML',
    'getFilteredItemsIDs',
);
foreach ($currentFunctions as $fn) eval(extractFunctionSourceForDbDiff($currentSource, $fn));

$legacySource = shell_exec('git -C ' . escapeshellarg($root) . ' show HEAD:Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
diffAssert(is_string($legacySource) && $legacySource !== '', 'must be able to read legacy source from HEAD');
$legacyIQ = extractFunctionSourceForDbDiff($legacySource, 'IQ_getFilteredItemsIDs');
$legacyGet = extractFunctionSourceForDbDiff($legacySource, 'getFilteredItemsIDs');
$legacyIQ = str_replace('function IQ_getFilteredItemsIDs(', 'function Legacy_IQ_getFilteredItemsIDs(', $legacyIQ);
$legacyGet = str_replace('function getFilteredItemsIDs(', 'function Legacy_getFilteredItemsIDs(', $legacyGet);
$legacyGet = str_replace('IQ_getFilteredItemsIDs(', 'Legacy_IQ_getFilteredItemsIDs(', $legacyGet);
eval($legacyIQ . "\n" . $legacyGet);

$returnProperties = array(
    array('ID' => 10, 'name' => 'name'),
    array('ID' => 11, 'name' => 'status'),
    array('ID' => 12, 'name' => 'bucket'),
    array('ID' => 30, 'name' => 'owner'),
);
$returnPropertiesWithTranslation = array(
    array('ID' => 10, 'name' => 'name'),
    array('ID' => 30, 'name' => 'owner', 'trName' => 'ownertrs'),
);
$returnPropertiesMainValue = array(
    array('ID' => 10, 'name' => 'mainValue'),
    array('ID' => 11, 'name' => 'status'),
);

$scenarios = array(
    array('unfiltered-limit', array(), $returnProperties, '', false, '25', '', 'AND', 0, false),
    array('filter-equals', array(array('ID' => 11, 'value' => 'active', 'mode' => '=')), $returnProperties, '', false, '40', '', 'AND', 0, false),
    array('filter-default-left-join', array(array('ID' => 11, 'value' => 'active', 'mode' => '=')), $returnProperties, '', false, '60', '', 'AND', 0, false),
    array('filter-like', array(array('ID' => 12, 'value' => 'bucket-3', 'mode' => 'LIKE')), $returnProperties, '', false, '30', '', 'AND', 0, false),
    array('ids-subset', array(), $returnProperties, '', false, '', '3,7,11,15,19,23,27', 'AND', 0, false),
    array('order-mainValue', array(), $returnPropertiesMainValue, 'mainValue', false, '35', '', 'AND', 0, false),
    array('order-return-property', array(), $returnProperties, 'name', false, '35', '', 'AND', 0, false),
    array('return-order', array(), $returnPropertiesWithTranslation, '', false, '20', '', 'AND', 1, false),
    array('translate-identifiers', array(), $returnPropertiesWithTranslation, '', true, '50', '', 'AND', 0, false),
    array('large-file-results', array(), $returnProperties, '', false, '', '', 'AND', 0, true),
);

$iterations = 7;
echo "Benchmark iterations per side: {$iterations}\n";
echo "Times are median wall-clock milliseconds on local MariaDB.\n";
foreach ($scenarios as $scenario) {
    list($name, $filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile) = $scenario;
    benchmarkScenario($name, $filters, $returns, $orderBy, $translate, $limit, $ids, $join, $returnOrder, $allowFile, $iterations);
}

$mysqli->query('DROP DATABASE `' . $dbName . '`');
echo "dynamic item join MariaDB benchmark completed\n";
