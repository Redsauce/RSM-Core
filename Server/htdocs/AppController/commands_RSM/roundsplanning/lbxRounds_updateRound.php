<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$roundID   = $GLOBALS['RS_POST']['roundID'];
$roundName = base64_decode($GLOBALS['RS_POST']['roundName']);

// rename
$roundNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningName'], $clientID);
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);

setPropertyValueByID($roundNamePropertyID,$itemTypeID, $roundID, $clientID, $roundName, '', $RSuserID);

$results['result'] = 'OK';

RSReturnArrayResults($results);
?>