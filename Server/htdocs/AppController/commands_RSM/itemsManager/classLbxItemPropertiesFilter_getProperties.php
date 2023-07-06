<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMuserPropertiesManagement.php";

// definitions
$itemTypeID = $GLOBALS['RS_POST']['itemtypeID'];
$clientID   = $GLOBALS['RS_POST']['clientID'];
$userID     = $GLOBALS['RS_POST']['userID'];

$results = getUserProperties($userID, $clientID, $itemTypeID);

// And return XML response back to application
RSreturnArrayQueryResults($results);
