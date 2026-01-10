<?php
//***************************************************
//Description:
//	Get the passed operation concepts
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Get the parameters to work with
isset($GLOBALS[$cstRS_POST][$cstClientID]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["IDs"     ]) ? $itemIDs  = $GLOBALS[$cstRS_POST]["IDs"     ] : dieWithError(400);

$itemIDs = explode(",", $itemIDs);
$results = array();

foreach ($itemIDs as $itemID) {
	$result = array();
	
	$result["name" ] = getPropertyValue("catalogItem.name" , "catalogItem", $itemID, $clientID);
	$result["price"] = getPropertyValue("catalogItem.price", "catalogItem", $itemID, $clientID);
	
	$results[] = $result;
}

RSReturnArrayQueryResults($results);
