<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions

$clientID           = $GLOBALS[$cstRS_POST][$cstClientID];
$itemTypeID         = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$pointerPropertyIDs = explode(',', $GLOBALS[$cstRS_POST][$cstPointerPropertyIDs]);
$pointerItemID      = $GLOBALS[$cstRS_POST][$cstPointerItemID];

// create the item
$itemID = createItem($itemTypeID, $clientID);

// update identifiers properties
foreach ($pointerPropertyIDs as $pointerPropertyID)
    setPropertyValueByID($pointerPropertyID, $itemTypeID, $itemID, $clientID, $pointerItemID, '', $RSuserID);

$results['ID'] = $itemID;

// Return data			
RSReturnArrayResults($results);
