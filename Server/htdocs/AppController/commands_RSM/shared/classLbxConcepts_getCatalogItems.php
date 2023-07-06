<?php
//***************************************************
//Description:
//  Get the passed operation concepts
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Get the parameters to work with
isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["IDs"]) ? $itemIDs  = $GLOBALS["RS_POST"]["IDs"] : dieWithError(400);

$itemIDs = explode(",", $itemIDs);
$results = array();

foreach ($itemIDs as $itemID) {
    $result = array();

    $result["name"] = getPropertyValue("catalogItem.name", $itemID, $clientID);
    $result["price"] = getPropertyValue("catalogItem.price", $itemID, $clientID);

    $results[] = $result;
}

RSreturnArrayQueryResults($results);
