<?php
require('../../utilities/RStools.php');
require_once "../../utilities/RSdatabase.php";

$endpoint = explode("/v2",$_SERVER['HTTP_REFERER'])[1];
$RStoken = getallheaders()["Authorization"];
$requestMethod = $_SERVER["REQUEST_METHOD"];

switch ($endpoint) {

    case '/items':
        switch ($requestMethod) {
            case 'GET':
                require_once "./items/getItems.php";
                break;
            case 'POST':
                require_once('./items/createItems.php');
                break;
            case 'PUT':
                require_once('./items/updateItems.php');
                break;
            case 'DELETE':
                require_once('./items/deleteItems.php');
                break;
            default:
                dieWithError(400, "Bad request");
                break;
    }
}
?>