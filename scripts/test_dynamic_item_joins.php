<?php

// Regression tests for the dynamic item join optimization. These tests run the
// real helper functions extracted from RSMitemsManagement.php against lightweight
// doubles, so they do not need a database or endpoint credentials.
// Prueba rapida sin BD: valida rutas, orden, traduccion y XML.

$root = dirname(__DIR__);
$sourcePath = $root . '/Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php';
$source = file_get_contents($sourcePath);
if ($source === false) {
    fwrite(STDERR, "Unable to read RSMitemsManagement.php\n");
    exit(1);
}

function dynamicJoinAssert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

function extractFunctionSource($source, $functionName) {
    $needle = 'function ' . $functionName . '(';
    $start = strpos($source, $needle);
    dynamicJoinAssert($start !== false, $functionName . ' must exist');

    $brace = strpos($source, '{', $start);
    dynamicJoinAssert($brace !== false, $functionName . ' must have a body');

    $depth = 0;
    $length = strlen($source);
    for ($i = $brace; $i < $length; $i++) {
        $char = $source[$i];
        if ($char === '{') $depth++;
        if ($char === '}') $depth--;
        if ($depth === 0) return substr($source, $start, $i - $start + 1);
    }

    dynamicJoinAssert(false, $functionName . ' body must close');
}

$getFilteredSource = extractFunctionSource($source, 'getFilteredItemsIDs');
$cachedPropertyTypeSource = extractFunctionSource($source, 'RSMgetFilteredPropertyType');
$cachedPropertyDefaultSource = extractFunctionSource($source, 'RSMgetFilteredPropertyDefaultValue');
$cachedMainPropertySource = extractFunctionSource($source, 'RSMgetFilteredMainPropertyID');
$idOnlySource = extractFunctionSource($source, 'IQ_getFilteredItemIDsOnly');
$hydrateSource = extractFunctionSource($source, 'RShydrateFilteredItemsProperties');
$arrayXmlSource = extractFunctionSource($source, 'RSfilteredItemsArrayToXML');
$filterClauseSource = extractFunctionSource($source, '_getFilterClause');
$translatedFilterClauseSource = extractFunctionSource($source, '_getTranslatedFilterClause');
$translatedFilterSubquerySource = extractFunctionSource($source, '_getTranslatedFilterSubquery');

// Static guard: getFilteredItemsIDs must not route back to the legacy joined-return query.
dynamicJoinAssert(strpos($getFilteredSource, 'IQ_getFilteredItemIDsOnly(') !== false, 'getFilteredItemsIDs must call the optimized ID-only query');
dynamicJoinAssert(strpos($getFilteredSource, 'IQ_getFilteredItemsIDs(') === false, 'getFilteredItemsIDs must not call the legacy joined-return query');
dynamicJoinAssert(strpos($idOnlySource, "rs_items.RS_ORDER AS 'ITEM_ORDER'") !== false, 'ID-only query must preserve returnOrder');
dynamicJoinAssert(strpos($idOnlySource, 'orderValue.RS_DATA') !== false, 'ID-only query must support order-only property joins');
dynamicJoinAssert(strpos($getFilteredSource, 'RSfilteredItemsArrayToXML(') !== false, 'large file-result path must use optimized array XML writer');
// Static guard: the cache must be created inside getFilteredItemsIDs(), not as global state.
dynamicJoinAssert(strpos($getFilteredSource, '$metadataCache = (') !== false, 'optimized path must keep metadata cache local to one call');

class DynamicJoinFakeResult {
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
$dynamicJoinQueries = array();
$dynamicJoinRows = array(array('ID' => 2), array('ID' => 1));
$dynamicJoinTranslated = false;
$dynamicJoinPropertyTypeCalls = array();

function RSQuery($query, $registerError = true) {
    global $dynamicJoinQueries, $dynamicJoinRows;
    $dynamicJoinQueries[] = $query;
    return new DynamicJoinFakeResult($dynamicJoinRows);
}

function getPropertyType($propertyID, $clientID) {
    // Count calls so the test can prove the cache removes duplicated metadata reads.
    global $dynamicJoinPropertyTypeCalls;
    $key = $clientID . ':' . $propertyID;
    if (!isset($dynamicJoinPropertyTypeCalls[$key])) $dynamicJoinPropertyTypeCalls[$key] = 0;
    $dynamicJoinPropertyTypeCalls[$key]++;
    if ($propertyID == 30) return 'identifier';
    return 'text';
}

function getClientPropertyDefaultValue($propertyID, $clientID) { return ''; }
function getMainPropertyID($itemTypeID, $clientID) { return 20; }
function getClientPropertyReferredItemType($propertyID, $clientID) { return 99; }
function isIdentifier($propertyID, $clientID, $typeName = '') { return $typeName == 'identifier' || $propertyID == 30; }
function isSingleIdentifier($propertyType) { return $propertyType == 'identifier'; }
function isMultiIdentifier($propertyType) { return $propertyType == 'identifiers'; }
function convertData($fieldName, $fieldType) { return $fieldName; }
function getItemsPropertyValues($propertyID, $clientID, $itemIDs = '', $propertyType = '', $itemTypeID = '', $translateIds = false, $returnOrder = 0, &$orderArray = array()) {
    if ($returnOrder) {
        $orderArray[1] = '4';
        $orderArray[2] = '3';
    }
    if ($propertyID == 10) return array(1 => 'one', 2 => 'two');
    if ($propertyID == 30) return array(1 => '100', 2 => '200');
    return array();
}
function _translateIds($results, $propertiesToTranslate, $clientID) {
    global $dynamicJoinTranslated;
    $dynamicJoinTranslated = count($propertiesToTranslate) > 0;
    foreach ($results as $index => $row) {
        $results[$index]['translated'] = $dynamicJoinTranslated ? 'yes' : 'no';
    }
    return $results;
}
function applyExternalFilters($itemTypeID, $clientID, $results, $extFilterRules) { return $results; }
function getItemTypeIDFromProperties($propertiesID, $clientID) { return 1; }
function getTreePath($clientID, &$treePath, $root, $itemTypeID, $allowedItemTypes, $level) { $treePath = array(); }
function getPathsForItem($clientID, $itemTypeID, $itemID, $treePath, $level, $path) { return array(); }
function getTranslatedValue($clientID, $property, $sourceValue) { return 'translated-' . $sourceValue; }

// Evaluate the actual optimized functions after defining their dependencies.
eval($cachedPropertyTypeSource);
eval($cachedPropertyDefaultSource);
eval($cachedMainPropertySource);
eval($filterClauseSource);
eval($translatedFilterClauseSource);
eval($translatedFilterSubquerySource);
eval($idOnlySource);
eval($hydrateSource);
eval($arrayXmlSource);
eval($getFilteredSource);

$returnProperties = array(
    array('ID' => 10, 'name' => 'name'),
    array('ID' => 30, 'name' => 'owner', 'trName' => 'ownertrs'),
);

// Runtime path: order by returned property uses a single order-only join, not returnValue joins.
$dynamicJoinQueries = array();
IQ_getFilteredItemIDsOnly(5, 7, array(), $returnProperties, 'name', '10', '', 'AND', 0);
$orderQuery = $dynamicJoinQueries[0];
dynamicJoinAssert(strpos($orderQuery, 'orderValue') !== false, 'property order path must add orderValue join');
dynamicJoinAssert(strpos($orderQuery, 'returnValue') === false, 'property order path must not add return-property joins');
dynamicJoinAssert(strpos($orderQuery, 'ORDER BY orderValue.RS_DATA') !== false, 'property order path must order by orderValue data');
dynamicJoinAssert(strpos($orderQuery, 'LIMIT 10') !== false, 'ID-only query must preserve limit');


// Runtime path: metadata cache is local to one optimized call.
// Property 10 is used twice in this scenario: once to build ORDER BY and once to hydrate.
// With the cache active, getPropertyType(10, 7) must be called only one time.
$dynamicJoinPropertyTypeCalls = array();
$dynamicJoinRows = array(array('ID' => 1));
getFilteredItemsIDs(5, 7, array(), $returnProperties, 'name', false, '', '', 'AND', 0, false, '', false);
dynamicJoinAssert(isset($dynamicJoinPropertyTypeCalls['7:10']) && $dynamicJoinPropertyTypeCalls['7:10'] === 1, 'property type cache must reuse order metadata during hydration');

// Runtime path: mainValue ordering joins only the main property for ordering.
$dynamicJoinQueries = array();
IQ_getFilteredItemIDsOnly(5, 7, array(), $returnProperties, 'mainValue', '', '', 'AND', 0);
$mainOrderQuery = $dynamicJoinQueries[0];
dynamicJoinAssert(strpos($mainOrderQuery, 'orderValue.RS_PROPERTY_ID = 20') !== false, 'mainValue path must order through the item type main property');

// Runtime path: returnOrder keeps ITEM_ORDER in the ID query and property order values during hydration.
$dynamicJoinQueries = array();
$dynamicJoinRows = array(array('ID' => 2, 'ITEM_ORDER' => 8), array('ID' => 1, 'ITEM_ORDER' => 9));
$ordered = getFilteredItemsIDs(5, 7, array(), $returnProperties, '', false, '', '', 'AND', 1, false, '', false);
dynamicJoinAssert(isset($ordered[0]['ITEM_ORDER']) && $ordered[0]['ITEM_ORDER'] == 8, 'returnOrder path must preserve ITEM_ORDER');
dynamicJoinAssert(isset($ordered[0]['owner_ord']) && $ordered[0]['owner_ord'] == '3', 'returnOrder path must hydrate identifier order values');
dynamicJoinAssert(strpos($dynamicJoinQueries[0], "rs_items.RS_ORDER AS 'ITEM_ORDER'") !== false, 'returnOrder path must request ITEM_ORDER in SQL');

// Runtime path: translation happens after hydration using collected identifier metadata.
$dynamicJoinTranslated = false;
$dynamicJoinRows = array(array('ID' => 1));
$translated = getFilteredItemsIDs(5, 7, array(), $returnProperties, '', true, '', '', 'AND', 0, false, '', false);
dynamicJoinAssert($dynamicJoinTranslated === true, 'translateIds path must receive identifier metadata from hydration');
dynamicJoinAssert($translated[0]['translated'] === 'yes', 'translateIds path must run _translateIds after hydration');

// Runtime path: large allowFileResults responses still return an XML temp file.
$dynamicJoinRows = array();
for ($i = 1; $i <= 1001; $i++) {
    $dynamicJoinRows[] = array('ID' => $i);
}
$file = getFilteredItemsIDs(5, 7, array(), array(array('ID' => 10, 'name' => 'name')), '', false, '', '', 'AND', 0, true, '', false);
dynamicJoinAssert(is_string($file) && file_exists($file), 'large allowFileResults path must return a temp XML file');
$xml = file_get_contents($file);
@unlink($file);
dynamicJoinAssert(strpos($xml, '<RSRecordset>') !== false, 'large allowFileResults XML must contain RSRecordset');
dynamicJoinAssert(strpos($xml, 'name="ID"') !== false, 'large allowFileResults XML must contain ID columns');

echo "dynamic item join optimization tests passed\n";
