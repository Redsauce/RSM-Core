<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$itemID = $GLOBALS['RS_POST']['itemID'];
$pointerPropertyIDs = explode(',', $GLOBALS['RS_POST']['pointerPropertyIDs']);
$pointerItemID = $GLOBALS['RS_POST']['pointerItemID'];

// update identifiers properties
foreach ($pointerPropertyIDs as $pointerPropertyID) {
	addIdentifier($pointerItemID, $itemTypeID, $itemID, $pointerPropertyID, $clientID, $RSuserID);
}

$results['result'] = 'OK';

// Return data			
RSReturnArrayResults($results);
?>