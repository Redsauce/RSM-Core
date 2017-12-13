<?php
//
// classLbxItemPropertiesFilter_getProperties.php
// ---> updated for the v.3.10

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMuserPropertiesManagement.php";

// definitions
$itemTypeID = $GLOBALS['RS_POST']['itemtypeID'];
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];

$results = getUserProperties($userID,$clientID,$itemTypeID);

// And return XML response back to application			
RSReturnArrayQueryResults($results);
?>