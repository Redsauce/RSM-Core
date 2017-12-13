<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];



// get rounds item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);

// get rounds properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);

$roundsNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningName'], $clientID);


// get properties names (they will be assigned to the list columns)
if (in_array($roundsNamePropertyID , $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }

$results[0]['roundsplanning'] = getClientItemTypeName($itemTypeID, $clientID);

$results[0]['name'] =getClientPropertyName($roundsNamePropertyID, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::


// And return XML results
RSReturnArrayQueryResults($results);
?>