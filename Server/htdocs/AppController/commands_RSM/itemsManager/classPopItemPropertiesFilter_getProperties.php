<?php
//
// classLbxItemPropertiesFilter_getProperties.php
// ---> updated for the v.3.10

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMuserPropertiesManagement.php";

// definitions
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$userID = $GLOBALS[$cstRS_POST][$cstUserID];

$results = getUserProperties($userID,$clientID,$itemTypeID);

// And return XML response back to application			
RSReturnArrayQueryResults($results);
