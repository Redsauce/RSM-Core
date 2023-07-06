<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID           = $GLOBALS['RS_POST']['clientID'];
$itemTypeID         = $GLOBALS['RS_POST']['itemTypeID'];
$pointerPropertyIDs = explode(',', $GLOBALS['RS_POST']['pointerPropertyIDs']);
$pointerItemID      = $GLOBALS['RS_POST']['pointerItemID'];

// create the item
$itemID = createItem($itemTypeID, $clientID);

// update identifiers properties
foreach ($pointerPropertyIDs as $pointerPropertyID) {
    setPropertyValueByID($pointerPropertyID, $itemTypeID, $itemID, $clientID, $pointerItemID, '', $RSuserID);
}

$results['ID'] = $itemID;

// Return data
RSreturnArrayResults($results);
