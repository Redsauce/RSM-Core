<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMusersManagement.php";

RSReturnQueryResults(getUserActions($GLOBALS['RS_POST']['userID'], $GLOBALS['RS_POST']['clientID']));
?>