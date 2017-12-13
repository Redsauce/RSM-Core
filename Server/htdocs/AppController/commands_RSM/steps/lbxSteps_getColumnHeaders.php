<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];


// get studies item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

// get studies properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);

$stepsNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsName'], $clientID);

// get properties names (they will be assigned to the list columns)
if (in_array($stepsNamePropertyID , $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }

$results[0]['steps'] = getClientItemTypeName($itemTypeID, $clientID);

$results[0]['name'] =getClientPropertyName($stepsNamePropertyID, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::

// And return XML results
RSReturnArrayQueryResults($results);
?>