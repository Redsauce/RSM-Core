<?php   

$RStoken = getallheaders()["Authorization"];

switch ($_SERVER["REQUEST_METHOD"]) {
    
    case 'GET':
        //Read params
        $params = explode('/', $_GET["url"]);

        //Check params are correct.
        if (count($params)!=4) dieWithErrorJson(400, "Incorrect number of params");

        if ($params[0]!="itemType") dieWithErrorJson(400, "First param should be named 'itemType'");
        if (!is_numeric($params[1])) dieWithErrorJson(400, "itemType param should be an integer");

        if ($params[2]!="item") dieWithErrorJson(400, "Third param should be named 'item'");
        if (!is_numeric($params[3])) dieWithErrorJson(400, "item param should be an integer");

        if (!is_string($RStoken) || $RStoken=="") dieWithErrorJson(401, "Authorization missing");

        //Call getItem
        require_once './item/getItem.php';
        getItem($params[1], $params[3], $RStoken);
        break;
        
    default:
        dieWithError(400, "Bad request");
        break;
}
?>