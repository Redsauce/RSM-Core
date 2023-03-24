<?php
//TODO: Make database don't require next line.
// TODO: add comments in finished version
$GLOBALS['RS_POST']['RStoken'] = getallheaders()["Authorization"]; //We need this variable to exists in order for RSdatabase to work propertly.
require_once "../../utilities/RStools.php";
require_once "./utils/utils.php";
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../api_headers.php";

global $RSallowDebug;
$RSallowUncompressed = true;

$endpoint = explode("/v2", $_SERVER['HTTP_REFERER'])[1];
$requestMethod = explode("?", $_SERVER["REQUEST_URI"])[1];

$endpoint = "/items";
$requestMethod = $_SERVER["REQUEST_METHOD"];

switch ($endpoint) {
    case '/items':
        switch ($requestMethod) {
            case 'GET':
                require_once "./items/getItems.php";
                break;
            case 'POST':
                require_once "./items/createItems.php";
                break;
            case 'PUT':
                require_once('./items/updateItems.php');
                break;
            case 'DELETE':
                require_once('./items/deleteItems.php');
                break;
            default:
                if ($RSallowDebug) returnJsonMessage(400, "Request method " . $requestMethod . " is not supported for endpoint " . $endpoint);
                else returnJsonMessage(400, "");
                break;
        }
    default:
        if ($RSallowDebug) returnJsonMessage(404, "Endpoint " . $endpoint . " does not exist");
        else returnJsonMessage(400, "");
        break;
}
