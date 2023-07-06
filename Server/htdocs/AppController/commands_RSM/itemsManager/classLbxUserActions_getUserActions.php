<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMusersManagement.php";

RSreturnQueryResults(getUserActions($GLOBALS['RS_POST']['userID'], $GLOBALS['RS_POST']['clientID']));
