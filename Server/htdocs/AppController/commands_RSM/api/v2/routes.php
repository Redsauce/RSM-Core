<?php
require_once './router.php';
require_once "../../utilities/RStools.php";
require_once "./utils/utils.php";
setAuthorizationTokenOnGlobals();
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../api_headers.php";
require_once "../v2/utils/verifyBody.php";

$RSallowUncompressed = true;

//ITEMS

get('/RSM-Core/items', '/items/getItems.php');
get('/RSM-Core/itemTypes', '/items/getItemTypes.php');
get('/RSM-Core/audittrail', '/auditTrail/getAuditTrail.php');
get('/RSM-Core/file', '/file/getFile.php');
get('/RSM-Core/picture', '/picture/getPicture.php');
get('/RSM-Core/properties', '/properties/getProperties.php');
get('/RSM-Core/staffID', '/staff/getStaffID.php');
get('/RSM-Core/userID', '/user/getUserID.php');
get('/RSM-Core/itemFromProperty', '/items/itemFromProperty.php');
get('/RSM-Core/itemsCount', '/items/getItemsCount.php');
post('/RSM-Core/items', '/items/createItems.php');
patch('/RSM-Core/items', '/items/updateItems.php');
delete('/RSM-Core/items', '/items/deleteItems.php');

//NOT FOUND
if ($RSallowDebug) returnJsonMessage(404, "Endpoint not found");
else returnJsonMessage(404, "");
