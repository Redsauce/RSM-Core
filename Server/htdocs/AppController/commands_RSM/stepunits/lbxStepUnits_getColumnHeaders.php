<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];



// get stepUnits item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

// get stepUnits properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);

$stepUnitsNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsName'], $clientID);
$stepUnitsUnitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);

// get properties names (they will be assigned to the list columns)
if (in_array($stepUnitsNamePropertyID , $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }
if (in_array($stepUnitsUnitPropertyID , $propertiesAllowed)) { $unitAllowed = '1'; } else { $unitAllowed = '0'; }

$results[0]['stepUnits'] = getClientItemTypeName($itemTypeID, $clientID);
$results[0]['roundsItemTypeID'] = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);
$results[0]['roundsItemTypeName'] = getClientItemTypeName($results[0]['roundsItemTypeID'], $clientID);

$results[0]['name'] =getClientPropertyName($stepUnitsNamePropertyID, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::
$results[0]['unit'] = getClientPropertyName($stepUnitsUnitPropertyID, $clientID).'::'.$unitAllowed;


// And return XML results
RSReturnArrayQueryResults($results);
?>