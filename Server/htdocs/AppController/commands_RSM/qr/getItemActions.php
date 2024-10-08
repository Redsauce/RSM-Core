<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

$RSallowUncompressed = true;

isset($GLOBALS['RS_POST']['RSdata'  ]) ? $RSdata   = $GLOBALS['RS_POST']['RSdata'  ] : dieWithError(400);

// Check for encryption
if (substr($RSdata, 0, 3) != ":::") {
  // The RSdata is encrypted
  // TODO: Decrypt the RSdata
  RSError("Need decrypt");
} else {
  // Remove the header characters
  $RSdata = substr($RSdata, 3);
}

$items = explode(",", $RSdata);
$clientID   = $items[0];
$itemTypeID = $items[1];
$itemID     = $items[2];

if ($clientID   == "") RSReturnError("EMPTY CLIENT ID"   , 1);
if ($itemTypeID == "") RSReturnError("EMPTY ITEM TYPE ID", 1);
if ($itemID     == "") RSReturnError("EMPTY ITEM ID"     , 1);

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);
$triggerIDs = getTriggerIDs(array($itemTypeID), $clientID, "triggerTypeQR");
$actionIDs  = getActionIDsFromTriggerIDs($triggerIDs, $clientID);

$actions = array();

foreach ($actionIDs as $actionID) {
  $actions[":::" . $clientID . "," . $itemTypeID . "," . $itemID . "," . $actionID] = getActionName($actionID, $clientID);
}

// And return XML response back to application
RSReturnArrayResults($actions, false);
?>
