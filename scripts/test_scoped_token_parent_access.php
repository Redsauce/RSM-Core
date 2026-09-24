<?php
// Prueba las funciones y las API del proyecto con una base de datos temporal en memoria.
// Requiere las extensiones sqlite3 y mbstring de PHP. No usa la base de datos del servidor.
if (!class_exists('SQLite3')) throw new RuntimeException('Enable the sqlite3 extension to run these tests.');

function loadProductionFunctions($path, $names) {
    $tokens = token_get_all(file_get_contents($path));
    $loaded = array();
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $source = 'function'; $name = null; $depth = 0; $started = false;
        for ($i++; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $source .= is_array($token) ? $token[1] : $token;
            if ($name === null && is_array($token) && $token[0] === T_STRING) $name = $token[1];
            if ($token === '{') { $depth++; $started = true; }
            if ($token === '}') $depth--;
            if ($started && $depth === 0) break;
        }
        if (in_array($name, $names, true)) { eval($source); $loaded[] = $name; }
    }
    if (array_diff($names, $loaded)) throw new RuntimeException('Production function missing from ' . $path);
}

class ScopeResult {
    public $num_rows;
    private $rows;
    function __construct($result) {
        $this->rows = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $this->rows[] = $row;
        $this->num_rows = count($this->rows);
    }
    function fetch_assoc() { return array_shift($this->rows); }
}
class ScopeEscaper { function real_escape_string($s) { return SQLite3::escapeString($s); } }
class ScopeResponse extends Exception {
    public $body;
    function __construct($body, $status = 200) { parent::__construct('', $status); $this->body = $body; }
}
$db = new SQLite3(':memory:');
$db->enableExceptions(true);
$db->createFunction('FIND_IN_SET', function ($needle, $haystack) {
    $position = array_search((string) $needle, explode(',', (string) $haystack), true);
    return $position === false ? 0 : $position + 1;
}, 2);
$mysqli = new ScopeEscaper();
$queries = array();
function RSQuery($sql) {
    global $db, $queries;
    $queries[] = $sql;
    return new ScopeResult($db->query($sql));
}
$db->exec('CREATE TABLE rs_items (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_ORDER);
CREATE TABLE rs_categories (RS_CLIENT_ID, RS_CATEGORY_ID, RS_ITEMTYPE_ID);
CREATE TABLE rs_item_properties (RS_CLIENT_ID, RS_CATEGORY_ID, RS_PROPERTY_ID, RS_TYPE, referred);
CREATE TABLE rs_tokens (RS_ID, RS_CLIENT_ID, RS_TOKEN, RS_ENABLED, RS_MASTER_TEMPLATE, RS_PARENT_MASTER_TOKEN, RS_CUSTOMER_ITEM_TYPE_ID, RS_CUSTOMER_ITEM_ID);
CREATE TABLE rs_token_permissions (RS_TOKEN_ID, RS_PROPERTY_ID, RS_PERMISSION);
CREATE TABLE rs_property_identifiers (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA TEXT);
CREATE TABLE rs_property_multiIdentifiers (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA TEXT);
CREATE TABLE rs_property_text (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA TEXT);
INSERT INTO rs_items VALUES (7,50,60,0),(7,50,61,1),(8,50,60,0),(7,51,60,0),(7,51,70,0),(7,51,71,0),(7,52,80,0),(7,52,81,0);
INSERT INTO rs_categories VALUES (7,50,50),(7,51,51),(7,52,52);
INSERT INTO rs_item_properties VALUES (7,50,100,"text",0),(7,50,101,"file",0),(7,50,102,"image",0),(7,51,200,"identifier",50),(7,52,300,"identifiers",50);
INSERT INTO rs_property_identifiers VALUES (7,51,70,200,60),(7,51,71,200,61);
INSERT INTO rs_property_multiIdentifiers VALUES (7,52,80,300,"60,61"),(7,52,81,300,"160,61");
INSERT INTO rs_property_text VALUES (7,50,60,100,"own"),(7,50,61,100,"other");
INSERT INTO rs_tokens VALUES (1,7,"scoped",1,0,0,50,60),(2,7,"standard",1,0,0,0,0),(3,7,"partial",1,0,0,50,0),
(10,7,"master",1,1,0,0,0),(11,7,"child",1,0,10,50,60),(12,7,"no-permission",1,0,0,50,60),(13,7,"disabled",0,0,0,50,60);');
foreach (array(1,10) as $owner) foreach (array(100,101,102,200,300) as $property) foreach (array('READ','WRITE','DELETE','CREATE') as $permission) {
    $db->exec("INSERT INTO rs_token_permissions VALUES ($owner,$property,'$permission')");
}
$root = dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/';
loadProductionFunctions($root . 'utilities/RSMtokensManagement.php', array(
    'RSgetTokenMetadata','RSisTokenEnabled','RSgetEffectivePermissionTokenID','RSgetTokenCustomerScope',
    'RSisCustomerScopedToken','RSisTokenCustomerScopeValid','RSgetTokenCustomerItemTypeID','RSgetTokenCustomerItemID',
    'RShasTokenPermission','RShasTokenPermissions'
));
loadProductionFunctions($root . 'utilities/RSMitemsManagement.php', array(
    'RSMgetFilteredPropertyType','RSMgetFilteredPropertyDefaultValue','IQ_getFilteredItemIDsOnly',
    'RShydrateFilteredItemsProperties','getFilteredItemsIDs','RSgetTokenCustomerDependencyProperty',
    'RSappendTokenCustomerScopeFilter','RSitemMatchesTokenCustomerScope','RSitemsMatchTokenCustomerScope',
    'RSapplyTokenCustomerScopeToCreatePayload','RSstaffItemMatchesTokenCustomerScope','verifyItemExists'
));
function RSclientFromToken($RStoken) { global $db; return (int) $db->querySingle("SELECT RS_CLIENT_ID FROM rs_tokens WHERE RS_TOKEN='" . SQLite3::escapeString($RStoken) . "'"); }
function parseITID($id, $client) { return $id === 'parents' ? 50 : (int) $id; }
function parsePID($id, $client) { return (int) $id; }
function propertyRow($id) { global $db; return $db->querySingle('SELECT * FROM rs_item_properties WHERE RS_PROPERTY_ID=' . (int) $id, true); }
function getClientPropertyReferredItemType($id, $client) { return propertyRow($id)['referred']; }
function getPropertyType($id, $client) { return propertyRow($id)['RS_TYPE']; }
function getClientPropertyDefaultValue($id, $client) { return ''; }
function getItemTypeIDFromProperties($ids, $client) { global $db; return (int) $db->querySingle('SELECT c.RS_ITEMTYPE_ID FROM rs_categories c JOIN rs_item_properties p USING (RS_CLIENT_ID,RS_CATEGORY_ID) WHERE p.RS_PROPERTY_ID=' . (int) $ids[0]); }
function getClientItemTypes($client) { return array(array('ID'=>50),array('ID'=>51),array('ID'=>52)); }
function getClientItemTypeProperties($type, $client) {
    $result = RSQuery('SELECT p.RS_PROPERTY_ID AS id, p.RS_TYPE AS type, "name" AS name FROM rs_item_properties p JOIN rs_categories c USING (RS_CLIENT_ID,RS_CATEGORY_ID) WHERE c.RS_ITEMTYPE_ID=' . (int) $type . ' AND c.RS_CLIENT_ID=' . (int) $client);
    $rows = array(); while ($row = $result->fetch_assoc()) $rows[] = $row; return $rows;
}
function getClientItemTypePropertiesId($type, $client) { return array_column(getClientItemTypeProperties($type,$client),'id'); }
function isSingleIdentifier($type) { return $type === 'identifier'; }
function isMultiIdentifier($type) { return $type === 'identifiers'; }
function isIdentifier($id, $client, $type) { return in_array($type,array('identifier','identifiers')); }
function isPropertyVisible($user, $property, $client) { return false; }
function arePropertiesVisible($user, $properties, $client) { return false; }
function convertData($expression, $type) { return $expression; }
function _getFilterClause($filter) { return 'filter' . $filter['ID'] . ".RS_DATA " . $filter['mode'] . " '" . SQLite3::escapeString($filter['value']) . "'"; }
function getClientItemTypeID_RelatedWith_byName($name, $client) { return 50; }
$definitions = array('staff'=>'staff');
$propertiesTables = array('text'=>'rs_property_text','identifier'=>'rs_property_identifiers','identifiers'=>'rs_property_multiIdentifiers');
$cstRS_POST='RS_POST'; $cstClientID='clientID'; $cstRStoken='RStoken'; $cstItemTypeID='itemTypeID';
$GLOBALS[$cstRS_POST] = array();
$checks = 0;
function expect($condition, $message) { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }

foreach (array('scoped','child') as $token) {
    expect(RSitemMatchesTokenCustomerScope($token,7,'parents',60), "$token own parent without self-reference");
    expect(!RSitemMatchesTokenCustomerScope($token,7,50,61), "$token other parent denied");
    expect(!RSitemMatchesTokenCustomerScope($token,8,50,60), "$token other client denied");
    expect(!RSitemMatchesTokenCustomerScope($token,7,51,60), "$token same numeric ID in another type denied");
    expect(RSitemMatchesTokenCustomerScope($token,7,51,70), "$token identifier relation allowed");
    expect(!RSitemMatchesTokenCustomerScope($token,7,51,71), "$token unrelated child denied");
    expect(RSitemMatchesTokenCustomerScope($token,7,52,80), "$token multi-identifier relation allowed");
    expect(!RSitemMatchesTokenCustomerScope($token,7,52,81), "$token substring is not an ID match");
    expect(!RSitemsMatchTokenCustomerScope($token,7,50,array(60,61)), "$token mixed batch denied");
    expect(!RSitemsMatchTokenCustomerScope($token,7,50,array()), "$token empty batch denied");
    expect(!RSitemMatchesTokenCustomerScope($token,7,50,'60 OR 1=1'), "$token malformed identity denied");
    expect(RSstaffItemMatchesTokenCustomerScope($token,7,60), "$token own staff parent allowed");
    expect(!RSstaffItemMatchesTokenCustomerScope($token,7,61), "$token other staff denied");
    expect(RSapplyTokenCustomerScopeToCreatePayload($token,7,50,array()) === false, "$token cannot create parent");
    expect(RSapplyTokenCustomerScopeToCreatePayload($token,7,51,array()) === array(array('ID'=>200,'value'=>60)), "$token related create still injects dependency");
}
foreach (array('partial','disabled','missing','master') as $token) {
    expect(!RSitemMatchesTokenCustomerScope($token,7,50,60) || !RSisTokenEnabled($token), "$token cannot authenticate/access parent");
}
$db->exec('UPDATE rs_tokens SET RS_ENABLED=0 WHERE RS_TOKEN="master"');
expect(!RSitemMatchesTokenCustomerScope('child',7,50,60), 'disabled master denies child parent scope');
$db->exec('UPDATE rs_tokens SET RS_ENABLED=1 WHERE RS_TOKEN="master"');
expect(RSitemMatchesTokenCustomerScope('standard',7,50,61), 'standard scope behavior unchanged');
expect(RSappendTokenCustomerScopeFilter('standard',7,50,array()) === array(), 'standard filters unchanged');
expect(RSappendTokenCustomerScopeFilter('scoped',8,50,array()) === false, 'query rejects another client');
$db->exec('DELETE FROM rs_items WHERE RS_CLIENT_ID=7 AND RS_ITEMTYPE_ID=50 AND RS_ITEM_ID=60');
expect(!RSitemMatchesTokenCustomerScope('scoped',7,50,60), 'nonexistent parent denied');
expect(getFilteredItemsIDs(50,7,RSappendTokenCustomerScopeFilter('scoped',7,50,array()),array()) === array(), 'missing parent query empty');
$db->exec('INSERT INTO rs_items VALUES (7,50,60,0)');

// Otro padre debe quedar excluido aunque tenga una relación con el padre del token.
$db->exec('INSERT INTO rs_item_properties VALUES (7,50,150,"identifier",50),(7,50,151,"identifier",50);
INSERT INTO rs_property_identifiers VALUES (7,50,61,150,60);');
expect(RSitemMatchesTokenCustomerScope('scoped',7,50,60), 'ambiguous dependencies do not block own parent');
expect(!RSitemMatchesTokenCustomerScope('scoped',7,50,61), 'linked sibling still denied');
$db->exec('DELETE FROM rs_property_identifiers WHERE RS_PROPERTY_ID=150;
DELETE FROM rs_item_properties WHERE RS_PROPERTY_ID IN (150,151);');
$db->exec('DELETE FROM rs_token_permissions WHERE RS_TOKEN_ID=1 AND RS_PROPERTY_ID=200 AND RS_PERMISSION="WRITE"');
$db->exec('INSERT INTO rs_token_permissions VALUES (1,200,"WRITE")');

// Ejecuta las consultas y comprueba que los filtros, el orden y la paginación
// nunca devuelven otro padre.
$ownFilter = array(array('ID'=>100,'value'=>'own','mode'=>'='));
$otherFilter = array(array('ID'=>100,'value'=>'other','mode'=>'='));
foreach (array('AND','OR') as $operator) {
    $filters = RSappendTokenCustomerScopeFilter('scoped',7,50,array_merge($ownFilter,$otherFilter));
    $rows = getFilteredItemsIDs(50,7,$filters,array(),'rs_items.RS_ITEM_ID',false,'1','60,61',$operator);
    expect(array_column($rows,'ID') === ($operator === 'OR' ? array(60) : array()), "$operator query isolation and intersection");
}
$filters = RSappendTokenCustomerScopeFilter('scoped',7,50,array());
expect(array_column(getFilteredItemsIDs(50,7,$filters,array()),'ID') === array(60), 'list/count only own parent');
expect(getFilteredItemsIDs(50,7,$filters,array(),'',false,'1 OFFSET 1') === array(), 'pagination after identity filter');
expect(getFilteredItemsIDs(50,7,$filters,array(),'',false,'','61') === array(), 'explicit IDs cannot widen scope');
expect(getFilteredItemsIDs(50,7,RSappendTokenCustomerScopeFilter('scoped',7,50,$otherFilter),array()) === array(), 'search excluding own parent returns no rows');

// Ejecuta el código de las API, sustituyendo las respuestas y las operaciones de escritura.
// Registra cada intento de escritura o borrado para comprobar que una petición rechazada
// no llega a modificar ningún item.
$effects = array(); $requestBody = null; $activeToken = 'scoped';
function getRStoken() { global $activeToken; return $activeToken; }
function getRSuserID() { return 0; }
function getRequestBody() { global $requestBody; return $requestBody; }
function handleApiCorsPreflight($methods) {}
function setAuthorizationTokenOnGlobals() {}
function checkCorrectRequestMethod($method) {}
function checkIsArray($value) { if (!is_array($value)) throw new ScopeResponse('invalid array',400); }
function checkIsJsonObject($value) { if (!is_object($value)) throw new ScopeResponse('invalid object',400); }
function checkBodyContains($value,$key) { if (!property_exists($value,$key)) throw new ScopeResponse('missing field',400); }
function checkStringIsInteger($value) { if (!ctype_digit((string)$value)) throw new ScopeResponse('invalid integer',400); }
function returnJsonMessage($status,$message) { throw new ScopeResponse($message,$status); }
function returnJsonResponse($body) { throw new ScopeResponse(json_decode($body,true)); }
function RSReturnArrayResults($body,$compress=false) { throw new ScopeResponse($body); }
function RSReturnArrayQueryResults($body,$compress=false) { throw new ScopeResponse($body); }
function dieWithError($status,$message='') { throw new ScopeResponse($message,$status); }
function isBase64($value) { return base64_decode($value,true) !== false; }
function replaceUtf8Characters($value) { return $value; }
function setPropertyValueByID($property,$type,$item,$client,$value,$propertyType='') { global $effects; $effects[]=array('write',$item,$property); return 0; }
function setDataPropertyValueByID($property,$type,$item,$client,$name,$value,$propertyType,$user) { global $effects; $effects[]=array('media',$item,$property); return 0; }
function deleteItemPropertyValue($type,$item,$property,$client,$propertyType='') { global $effects; $effects[]=array('clear',$item,$property); return 0; }
function deleteItem($type,$item,$client) { global $effects; $effects[]=array('delete',$item); }
function deleteItems($type,$client,$ids='') { global $effects; $effects[]=array('delete',$ids); }
function getItemDataPropertyValue($item,$property,$client) { return 'parent-data'; }
function getItemPropertyValue($item,$property,$client) { return 'file.txt:4'; }
function getAppPropertyName_RelatedWith($property,$client) { return ''; }
function getAppListValueID($value) { return 0; }
function countItemsResult($items) { return count($items); }
function checkBodyContainsAtLeastOne($body,...$keys) { foreach ($keys as $key) if (isset($body->$key)) return; throw new ScopeResponse('missing fields',400); }
function checkArrayContains($array,$key) { if (!array_key_exists($key,$array)) throw new ScopeResponse('missing field',400); }
function checkParamsContains($array,$key) { checkArrayContains($array,$key); }
function checkADJParamIsValid($parameters) { expect($parameters['adj'] === 's','valid picture adjustment'); }
function checkArrayContainsAtLeastOne($array,...$keys) { foreach ($keys as $key) if (isset($array[$key])) return; throw new ScopeResponse('missing fields',400); }
function getRequestParams() { return $GLOBALS['RS_GET']; }
function RShasREADTokenPermission($token,$property) { return RShasTokenPermission($token,$property,'READ'); }
function getFile($client,$property,$item) { throw new ScopeResponse('media-read'); }
function getImage($client,$property,$item) { throw new ScopeResponse('media-read'); }
function runEndpoint($path,$post=array(),$body=null,$token='scoped') {
    global $root,$effects,$requestBody,$activeToken,$cstRS_POST,$cstClientID,$cstRStoken,$cstItemTypeID;
    $effects=array(); $requestBody=$body; $activeToken=$token;
    $GLOBALS[$cstRS_POST]=array_merge(array('clientID'=>7,'RStoken'=>$token),$post);
    $GLOBALS['RS_GET']=$GLOBALS[$cstRS_POST]; $_GET=array();
    $RSuserID=0; $RSallowDebug=true;
    $RSfileCache=$RSimageCache=__DIR__ . '/nonexistent-scope-test-cache';
    $enable_file_cache=$enable_image_cache=false;
    $source=file_get_contents($root . 'api/' . $path);
    $source=preg_replace('/^\s*require_once\s+[^;]+;/m','',$source);
    preg_match_all('/function\s+(\w+)\(/',$source,$matches);
    if ($matches[1] && function_exists($matches[1][0] . '_' . md5($path))) $source=preg_replace('/function\s+\w+\([\s\S]*$/','',$source);
    foreach ($matches[1] as $name) $source=preg_replace('/\b' . $name . '\s*\(/',$name . '_' . md5($path) . '(',$source);
    try { eval('?>' . $source); } catch (ScopeResponse $response) { return $response; }
    return new ScopeResponse(null);
}
foreach (array('scoped','child','no-permission') as $token) {
    foreach (array(60,61) as $id) {
        $allowed=$token !== 'no-permission' && $id === 60;
        runEndpoint('api_updateItem.php',array('RSitemID'=>$id,'RSdata'=>'100:' . base64_encode('changed')),null,$token);
        expect((count($effects) === 1) === $allowed, "v1 update $token/$id");
        runEndpoint('v2/items/update.php',array(),array((object)array('ID'=>(string)$id,'100'=>'changed')),$token);
        expect((count($effects) === 1) === $allowed, "v2 update $token/$id");
        foreach (array(101,102) as $media) {
            runEndpoint('v2/items/update.php',array(),array((object)array('ID'=>(string)$id,(string)$media=>'name:content')),$token);
            expect((count($effects) === 1) === $allowed, "v2 media $token/$id/$media");
            runEndpoint('api_updateItem.php',array('RSitemID'=>$id,'RSdata'=>$media . ':' . base64_encode('name:content')),null,$token);
            expect((count($effects) === 1) === $allowed, "v1 media $token/$id/$media");
        }
        $response=runEndpoint('api_getItem.php',array('itemID'=>$id,'itemTypeID'=>50),null,$token);
        expect((is_array($response->body) && isset($response->body[0]['value'])) === $allowed, "v1 read $token/$id");
        foreach (array('api_getFile.php'=>101,'api_getPicture.php'=>102,'v2/file/get.php'=>101,'v2/picture/get.php'=>102) as $path=>$property) {
            $response=runEndpoint($path,array('itemID'=>$id,'ID'=>(string)$id,'propertyID'=>(string)$property,'w'=>'100','h'=>'100','adj'=>'s'),null,$token);
            expect(($response->body === 'media-read') === $allowed,"$path read $token/$id");
        }
        runEndpoint('api_deleteItem.php',array('itemID'=>$id,'itemTypeID'=>50),null,$token);
        expect((count($effects) === 1) === $allowed, "v1 delete $token/$id");
        runEndpoint('v2/items/delete.php',array(),array((object)array('itemTypeID'=>50,'IDs'=>array((string)$id))),$token);
        expect((count($effects) === 1) === $allowed, "v2 delete $token/$id");
    }
}
runEndpoint('api_updateItems.php',array('RSdata'=>base64_encode(json_encode(array(60=>array(100=>'new'),61=>array(100=>'new'))))));
expect(!$effects,'v1 mixed update has no partial writes');
runEndpoint('v2/items/update.php',array(),array((object)array('ID'=>'60','100'=>'new'),(object)array('ID'=>'61','100'=>'new')));
expect(!$effects,'v2 mixed update has no partial writes');
runEndpoint('api_deleteItems.php',array('itemTypeID'=>50,'itemIDs'=>'60,61'));
expect(!$effects,'v1 mixed delete has no partial effects');
foreach (array(array('60','61'),array('60 OR 1=1')) as $ids) {
    runEndpoint('v2/items/delete.php',array(),array((object)array('itemTypeID'=>50,'IDs'=>$ids)));
    expect(!$effects,'v2 mixed/malformed delete has no effects');
}
foreach (array('scoped','child','standard','no-permission') as $token) {
    $response=runEndpoint('v2/items/delete.php',array(),array((object)array('itemTypeID'=>50,'IDs'=>array())),$token);
    expect($response->getCode() === 200 && !$effects,"v2 empty delete succeeds without effects for $token");
}
$response=runEndpoint('v2/items/delete.php',array(),array(
    (object)array('itemTypeID'=>50,'IDs'=>array()),
    (object)array('itemTypeID'=>50,'IDs'=>array('60'))
));
expect($response->getCode() === 200 && $effects === array(array('delete','60')),'v2 skips empty group and deletes only explicit allowed IDs');
$response=runEndpoint('v2/items/delete.php',array(),array(
    (object)array('itemTypeID'=>50,'IDs'=>array()),
    (object)array('itemTypeID'=>50,'IDs'=>array('61'))
));
expect($response->getCode() === 403 && !$effects,'v2 empty group does not bypass rejection of forbidden IDs');
foreach (array('scoped','child') as $token) {
    $response=runEndpoint('v2/items/get.php',array(),(object)array('itemTypeID'=>50,'propertyIDs'=>array(100),'IDs'=>array(60,61)),$token);
    expect(array_map('intval',array_column($response->body,'ID')) === array(60),"v2 parent query $token");
    $response=runEndpoint('v2/items/get.php',array(),(object)array('itemTypeID'=>50,'propertyIDs'=>array(100),'IDs'=>array(61)),$token);
    expect($response->body === array(),"v2 sibling query $token");
    $response=runEndpoint('v2/items/getCount.php',array(),(object)array('itemTypeID'=>50,'propertyIDs'=>array(100)),$token);
    expect($response->body === array('count'=>1),"v2 parent count $token");
    $response=runEndpoint('api_getItems.php',array('propertyIDs'=>'100'),null,$token);
    expect(array_map('intval',array_column((array)$response->body,'ID')) === array(60),"v1 parent query $token");
    $response=runEndpoint('api_getItemsCount.php',array('itemTypeID'=>50),null,$token);
    expect($response->body === array('total'=>1),"v1 parent count $token");
    $response=runEndpoint('api_getItems.php',array('propertyIDs'=>'100','filterJoining'=>'OR','filterRules'=>'100;' . base64_encode('other') . ';='),null,$token);
    expect(count($response->body) === 0,"v1 OR filter cannot disclose sibling $token");
}

// Prueba la consulta del árbol y los items que devuelve.
// Simula un hijo con dos padres para comprobar que el otro padre no aparece en la respuesta.
loadProductionFunctions($root . 'api/v2/items/getTree.php',array('getTreeFlatItems','filterItemsForToken','filterAssignedItemsForStaff'));
loadProductionFunctions($root . 'utilities/RSMfiltersManagement.php',array('combineItemPaths'));
function getMainPropertyID($type,$client) { return 100; }
function _translateIds($rows,$properties,$client) { return $rows; }
function getTreePath($client,&$paths,$parents,$destination,$allowed,$depth) { $paths=array(array(array('itemTypeID'=>50,'mainPropertyID'=>100,'mainPropertyType'=>'text'))); }
function getItemsPropertyValues($property,$client,$ids='',$type='',$itemType='') { return array(60=>'own',61=>'other'); }
function buildTreeNodeKey($type,$id) { return $type . ':' . $id; }
function getPathsForItem($client,$type,$id,$paths,$parentID,$extra,$properties,$main,$returnOrder,$orders) {
    return array(
        array('nodeItemType'=>50,'nodeID'=>'60','name'=>'own','parentItemType'=>50,'parentID'=>0,'childs'=>'61,50'),
        array('nodeItemType'=>50,'nodeID'=>'61','name'=>'other','parentItemType'=>50,'parentID'=>0,'childs'=>''),
        array('nodeItemType'=>50,'nodeID'=>'60','name'=>'own','parentItemType'=>50,'parentID'=>'61','childs'=>'')
    );
}
$tree=getTreeFlatItems(7,'scoped',50,0,array(50),array(50),0,'',0);
expect(count($tree) === 1 && $tree[0]['nodeID'] === '60' && $tree[0]['childs'] === '', 'tree hides sibling nodes, parent IDs and child IDs');
expect(getTreeFlatItems(7,'partial',50,0,array(50),array(50),0,'',0) === array(), 'invalid tree scope denied');

// Añade otro padre relacionado con el padre del token.
// Comprueba que no se permite crear más padres ni borrar el propio si eso modifica al otro.
// Ejecuta también las API de borrado para comprobar que rechazan la petición antes de borrar.
$db->exec('INSERT INTO rs_item_properties VALUES (7,50,150,"identifier",50);
INSERT INTO rs_property_identifiers VALUES (7,50,61,150,60);
INSERT INTO rs_token_permissions VALUES (1,150,"DELETE");');
expect(RSapplyTokenCustomerScopeToCreatePayload('scoped',7,50,array(array('ID'=>150,'value'=>60))) === false,'parent create with matching self-type dependency denied');
$db->exec('DELETE FROM rs_token_permissions WHERE RS_TOKEN_ID=1 AND RS_PERMISSION="WRITE"');
runEndpoint('api_deleteItem.php',array('itemID'=>60,'itemTypeID'=>50));
expect($effects === array(array('delete',60)),'v1 deletion preserves existing reference handling');
runEndpoint('v2/items/delete.php',array(),array((object)array('itemTypeID'=>50,'IDs'=>array('60'))));
expect($effects === array(array('delete','60')),'v2 deletion preserves existing reference handling');
echo "Scoped parent access: $checks checks passed.\n";
