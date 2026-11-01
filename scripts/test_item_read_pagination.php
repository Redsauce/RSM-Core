<?php
// Run with PHP sqlite3 and mbstring enabled. Reuse the isolated SQLite endpoint
// fixture: no server, production credentials or database changes are required.
ob_start(); // Keep fixture output buffered while endpoints set HTTP headers.
require __DIR__ . '/test_scoped_token_parent_access.php';
require_once $root . 'utilities/RSresponseTempFiles.php';
loadProductionFunctions($root . 'utilities/RSMitemsManagement.php', array(
    'RSstreamFilteredItemsPropertiesToXML', 'RSfilteredItemsArrayToXML'
));
function RSReturnError($message, $code) { throw new ScopeResponse($message, 400); }
function getUserVisiblePropertiesIDs($type, $client, $user) { return array(100, 400, 401); }
function RSReturnFileResults($file, $compressed = true) {
    $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);
    $rows = array();
    foreach ($xml->rows->row as $row) {
        $item = array();
        foreach ($row->column as $column) $item[(string)$column['name']] = (string)$column;
        $rows[] = $item;
    }
    RSDeleteResponseTempFile($file);
    throw new ScopeResponse($rows);
}
function applyExternalFilters($type, $client, $rows, $rules) {
    $GLOBALS['externalFilterCalls']++;
    return array_filter($rows, function ($row) { return $row['ID'] % 2 === 0; });
}
$RStempPath = sys_get_temp_dir();
$cstCDATAseparator = ']]]]><![CDATA[>';
$cstUTF8 = 'UTF-8';
$cstMainPropertyID = 'mainPropertyID';
$cstMainPropertyType = 'mainPropertyType';
$cstReferredItemTypeID = 'referredItemTypeID';
$RSresponseFileFieldThreshold = 2000;
$startChecks = $checks;

foreach (array(false, true) as $json) {
    expect(RSMparseItemPagination(array(), $json) === null, 'omitted pagination remains legacy');
    expect(RSMparseItemPagination(array('offset'=>'001', 'limit'=>'0499'), $json) === array('offset'=>1,'limit'=>499), 'canonical integer parsing');
    foreach (array(-1, 1.5, true, false, null, array(), (object)array(), ' ', '1e2', '1,5', '0 OFFSET 2', (string)PHP_INT_MAX . '0') as $invalid) {
        foreach (array('offset','limit') as $key) {
            $input = array('offset'=>0,'limit'=>1); $input[$key] = $invalid;
            $rejected = false;
            try { RSMparseItemPagination($input, $json); } catch (InvalidArgumentException $e) { $rejected = true; }
            expect($rejected, 'reject malformed ' . $key);
        }
    }
    $rejected = false;
    try { RSMparseItemPagination(array('offset'=>0), $json); } catch (InvalidArgumentException $e) { $rejected = true; }
    expect($rejected, 'offset requires count');
}
expect(RSMparseItemPagination(array('offset'=>'','limit'=>'1,5')) === null, 'empty form offset preserves composite limit');
expect(RSMparseItemPagination(array('limit'=>100), true) === array('offset'=>0,'limit'=>100), 'v2 count-only');
expect(RSMparseItemPagination(array('offset'=>PHP_INT_MAX,'limit'=>0), true)['offset'] === PHP_INT_MAX, 'integer maximum accepted');

// New type with large enough ID range to prove deep pages and ID != position.
$db->exec('ALTER TABLE rs_categories ADD COLUMN RS_NAME;
ALTER TABLE rs_categories ADD COLUMN RS_ORDER;
ALTER TABLE rs_item_properties ADD COLUMN RS_ORDER;
INSERT INTO rs_categories (RS_CLIENT_ID,RS_CATEGORY_ID,RS_ITEMTYPE_ID,RS_NAME,RS_ORDER) VALUES (7,54,54,"General",0);
INSERT INTO rs_item_properties (RS_CLIENT_ID,RS_CATEGORY_ID,RS_PROPERTY_ID,RS_TYPE,referred,RS_ORDER) VALUES (7,54,400,"variant",0,0),(7,54,401,"text",0,1);
INSERT INTO rs_token_permissions VALUES (2,400,"READ"),(2,401,"READ");');
for ($id = 1000; $id < 2100; $id++) {
    $db->exec("INSERT INTO rs_items VALUES (7,54,$id,0); INSERT INTO rs_property_text VALUES (7,54,$id,401,'same')");
}
$propertiesTables['variant'] = 'unused_variant_table';
$form = array('properties'=>'400,401','propertyIDs'=>'400,401','filters'=>'','filterRules'=>'', 'IDs'=>'','orderBy'=>'','join'=>'','orderPropertyID'=>'','limit'=>'');
$endpoints = array('../shared/classDataManager_getItems.php', 'api_getItems.php', 'v2/items/get.php');
foreach ($endpoints as $endpoint) {
    $v2 = $endpoint === 'v2/items/get.php';
    foreach (array(array(0,100,1000,100), array(100,100,1100,100), array(1,499,1001,499), array(999,1,1999,1), array(1090,100,2090,10), array(1100,100,0,0), array(0,0,0,0)) as $case) {
        list($offset,$limit,$first,$count) = $case;
        $post = array_merge($form, array('offset'=>(string)$offset,'limit'=>(string)$limit));
        $body = (object)array('itemTypeID'=>54,'propertyIDs'=>array('400','401'),'offset'=>$offset,'limit'=>$limit);
        $GLOBALS['scopePropertyReads'] = array();
        $response = runEndpoint($endpoint, $post, $body, 'standard');
        $ids = array_map('intval', array_column($response->body, 'ID'));
        expect(count($ids) === $count, "$endpoint page count $offset/$limit");
        if ($count) expect($ids === range($first, $first+$count-1), "$endpoint stable page $offset/$limit");
        if ($limit === 0 || $offset >= 1100) expect(!$GLOBALS['scopePropertyReads'], "$endpoint empty page does not hydrate");
        if ($offset === 999) {
            expect(count($GLOBALS['scopePropertyReads']) === 2, "$endpoint loads only requested page properties");
            foreach ($GLOBALS['scopePropertyReads'] as $read) expect($read[1] === '1999', "$endpoint never hydrates skipped variants");
        }
    }
    foreach (array(array('offset'=>'-1','limit'=>'1'), array('offset'=>'0','limit'=>null), array('offset'=>'0','limit'=>'1,2')) as $bad) {
        $before = count($queries);
        $response = runEndpoint($endpoint, array_merge($form,$bad), (object)array_merge(array('itemTypeID'=>54),$bad), 'standard');
        expect($response->getCode() === 400, "$endpoint invalid page rejected");
        expect(count($queries) === $before, "$endpoint invalid page rejected before SQL");
    }
}

// Legacy calls and v2 count-only continue through the same response layouts.
foreach (array('../shared/classDataManager_getItems.php','api_getItems.php') as $endpoint) {
    foreach (array(array('limit'=>'2'),array('offset'=>'','limit'=>'2')) as $legacy) {
        $response = runEndpoint($endpoint,array_merge($form,$legacy),null,'standard');
        expect(count($response->body) === 2, "$endpoint legacy count unchanged");
    }
}
$response = runEndpoint('../shared/classDataManager_getItems.php',array_merge($form,array('limit'=>'1,2')),null,'standard');
expect(array_map('intval',array_column($response->body,'ID')) === array(1001,1002), 'Data Manager legacy composite limit');
$response = runEndpoint('api_getItems.php',array_merge($form,array('limit'=>'1,2')),null,'standard');
expect($response->getCode() === 400, 'v1 still rejects composite limit');
$response = runEndpoint('v2/items/get.php',array(),(object)array('itemTypeID'=>54,'propertyIDs'=>array('401'),'limit'=>2),'standard');
expect(count($response->body) === 2, 'v2 count-only forwarding');
foreach (array('offset','limit') as $key) {
    $body=(object)array('itemTypeID'=>54,'offset'=>0,'limit'=>1);$body->$key='';
    expect(runEndpoint('v2/items/get.php',array(),$body,'standard')->getCode() === 400, 'v2 rejects empty JSON pagination');
}
foreach (array(false,true) as $categories) {
    $body=(object)array('itemTypeID'=>54,'propertyIDs'=>array('400','401'),'offset'=>2,'limit'=>2,'includeCategories'=>$categories,'systemNames'=>true,'translateIDs'=>true);
    $response=runEndpoint('v2/items/get.php',array(),$body,'standard');
    expect(array_map('intval',array_column($response->body,'ID')) === array(1002,1003), 'v2 output options preserve the page');
    expect(array_key_exists($categories ? 'General' : '400',$response->body[0]), 'v2 category/flat layout unchanged');
}

// Tied property ordering is stable and legacy queries gain no new ORDER BY.
$cache=array();
$result=IQ_getFilteredItemIDsOnly(54,7,array(),array(array('ID'=>401,'name'=>'401')),'401','', '', 'AND',0,$cache,array('offset'=>2,'limit'=>2));
expect(array($result->fetch_assoc()['ID'],$result->fetch_assoc()['ID']) === array(1002,1003), 'property ties use item ID');
$cache=array();IQ_getFilteredItemIDsOnly(54,7,array(),array(),'','2','','AND',0,$cache);
expect(strpos(end($queries),'ORDER BY') === false, 'legacy order is unchanged');

// Token identity scope is applied before offset/IDs, never after paging.
$filters=RSappendTokenCustomerScopeFilter('scoped',7,50,array());
foreach (array(0,1) as $offset) {
    $rows=getFilteredItemsIDs(50,7,$filters,array(),'',false,'','60,61','AND',0,false,'',false,array('offset'=>$offset,'limit'=>1));
    expect(array_column($rows,'ID') === ($offset ? array() : array(60)), 'scope and explicit IDs precede page');
}

// External filtering must run exactly once before slicing, even for file output.
foreach (array(false,true) as $fileOutput) {
    $GLOBALS['externalFilterCalls']=0;
    $rows=getFilteredItemsIDs(54,7,array(),array(array('ID'=>400,'name'=>'400')),'',false,'','','AND',0,$fileOutput,'external-fixture',false,array('offset'=>1,'limit'=>2));
    if (is_string($rows)) {
        try { RSReturnFileResults($rows); } catch (ScopeResponse $response) { $rows=$response->body; }
    }
    expect(array_map('intval',array_column($rows,'ID')) === array(1002,1004), 'slice surviving external results');
    expect($GLOBALS['externalFilterCalls'] === 1, 'external filter runs once');
}
RSCleanupResponseTempFiles();
foreach (array('../shared/classDataManager_getItems.php','api_getItems.php','v2/items/get.php') as $endpoint) {
    $source=file_get_contents($root . 'api/' . $endpoint);
    if ($endpoint === 'v2/items/get.php') {
        preg_match_all('/^\/\/ (\{"itemTypeID"[^\r\n]+\})\r?$/m', $source, $examples);
        foreach ($examples[1] as $example) {
            $input=json_decode($example,true,512,JSON_THROW_ON_ERROR);
            expect(RSMparseItemPagination($input,true) !== null, 'documented v2 JSON page is valid');
        }
    } else {
        preg_match_all('/^\/\/ (clientID=[^\r\n]+)\r?$/m', $source, $examples);
        foreach ($examples[1] as $example) {
            parse_str($example,$input);
            expect(RSMparseItemPagination($input) !== null, 'documented form page is valid');
        }
    }
    expect(count($examples[1]) === 3, 'each endpoint documents three page requests');
}
echo 'Item pagination: ' . ($checks-$startChecks) . " checks passed.\n";
ob_end_flush();
