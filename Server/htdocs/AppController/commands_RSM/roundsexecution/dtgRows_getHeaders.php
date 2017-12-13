<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID']  : dieWithError(400);
isset($GLOBALS['RS_POST']['userID'  ]) ? $userID   = $GLOBALS['RS_POST']['userID'  ]  : $userID = $RSuserID;

//get the Steps item type
$itemTypeStepsID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

// get stepUnits item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

//get Steps properties allowed
$stepsPropertiesAllowed = getVisibleProperties($itemTypeStepsID, $clientID, $userID);

// get stepUnits properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);


$stepUnitsNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsName'], $clientID);
$stepUnitsUnitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);
$stepsNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsName'], $clientID);

// get properties names (they will be assigned to the list columns)
if (in_array($stepUnitsNamePropertyID , $stepsPropertiesAllowed)) { $stepsNameAllowed = '1'; } else { $stepsNameAllowed = '0'; }
if (in_array($stepUnitsNamePropertyID , $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }
if (in_array($stepUnitsUnitPropertyID , $propertiesAllowed)) { $unitAllowed = '1'; } else { $unitAllowed = '0'; }

$results[0]['stepsName'] =getClientPropertyName($stepsNamePropertyID, $clientID).'::'.$stepsNameAllowed;
$results[0]['stepsUnitsName'] =getClientPropertyName($stepUnitsNamePropertyID, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::
$results[0]['stepsUnitsUnit'] = getClientPropertyName($stepUnitsUnitPropertyID, $clientID).'::'.$unitAllowed;


// And return XML results
RSReturnArrayQueryResults($results);
?>
