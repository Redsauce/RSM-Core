<?php
require_once __DIR__ . '/router.php';
//TODO: Make database don't require next line.
$GLOBALS['RS_POST']['RStoken'] = getallheaders()["Authorization"]; //We need this variable to exists in order for RSdatabase to work propertly.
require_once "../../utilities/RStools.php";
require_once "./utils/utils.php";
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../api_headers.php";

$RSallowUncompressed = true;

get('/RSM-Core/items', '/items/getItems.php');
post('/RSM-Core/items', '/items/createItems.php');
put('/RSM-Core/items', '/items/updateItems.php');
delete('/RSM-Core/items', '/items/deleteItems.php');
