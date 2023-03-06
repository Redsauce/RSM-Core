<?php
require('../../utilities/RStools.php');

//Check endpoint
$params = explode('/', $_GET["url"]);

if (in_array("item", $params)) {
    require_once './item/index.php';
} else {
    dieWithError(400, "Endpoint not defined");
}
?>