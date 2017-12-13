<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$stepID   = $GLOBALS['RS_POST']['stepID'];
$stepMainValue = base64_decode($GLOBALS['RS_POST']['mainValue']);
$stepDescription = base64_decode($GLOBALS['RS_POST']['description']);


// get item type ID
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

// get properties
$stepMainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$stepDescriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsDescription'],$clientID);


// update step
setPropertyValueByID($stepMainPropertyID ,$itemTypeID, $stepID, $clientID, $stepMainValue, '', $RSuserID);
setPropertyValueByID($stepDescriptionPropertyID ,$itemTypeID, $stepID, $clientID, $stepDescription, '', $RSuserID);


$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
?>