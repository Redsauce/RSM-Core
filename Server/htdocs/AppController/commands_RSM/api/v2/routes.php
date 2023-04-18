<?php
require_once './router.php';
require_once "../../utilities/RStools.php";
require_once "./utils/utils.php";
setAuthorizationTokenOnGlobals();
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../api_headers.php";

$RSallowUncompressed = true;

//ITEMS
get('/RSM-Core/items', '/items/getItems.php');
get('/RSM-Core/itemTypes', '/items/getItemTypes.php');
get('/RSM-Core/audittrail', '/auditTrail/getAuditTrail.php');
post('/RSM-Core/items', '/items/createItems.php');
patch('/RSM-Core/items', '/items/updateItems.php');
delete('/RSM-Core/items', '/items/deleteItems.php');


//NOT FOUND
if ($RSallowDebug) returnJsonMessage(404, "Endpoint not found");
else returnJsonMessage(404, "");
