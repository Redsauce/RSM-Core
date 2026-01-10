<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions

$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$itemID = $GLOBALS[$cstRS_POST][$cstItemID];
$pointerPropertyIDs = explode(',', $GLOBALS[$cstRS_POST][$cstPointerPropertyIDs]);
$pointerItemID = $GLOBALS[$cstRS_POST][$cstPointerItemID];

// update identifiers properties
foreach ($pointerPropertyIDs as $pointerPropertyID) {
	addIdentifier($pointerItemID, $itemTypeID, $itemID, $pointerPropertyID, $clientID, $RSuserID);
}

$results['result'] = 'OK';

// Return data			
RSReturnArrayResults($results);
