<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$newOrder = explode(',', $GLOBALS['RS_POST']['newOrder']);


// get item types
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

// get properties
$orderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);

// get order property type
$orderPropertyType = getPropertyType($orderPropertyID, $clientID);


// update orders
for ($i = 0; $i < count($newOrder); $i++) {
	setPropertyValueByID($orderPropertyID, $itemTypeID, $newOrder[$i], $clientID, $i+1, $orderPropertyType, $RSuserID);
}


$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
?>