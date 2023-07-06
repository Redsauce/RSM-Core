<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

$RSallowUncompressed = true;

isset($GLOBALS['RS_POST']['RSdata']) ? $RSdata            = $GLOBALS['RS_POST']['RSdata'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSLogin']) ? $RSLogin           = $GLOBALS['RS_POST']['RSLogin'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSuserMD5Password']) ? $RSuserMD5Password = $GLOBALS['RS_POST']['RSuserMD5Password'] : dieWithError(400);

// Check for encryption
if (substr($RSdata, 0, 3) != ":::") {
  // The RSdata is encrypted
  // TODO: Decrypt the RSdata
} else {
  // Remove the header characters
  $items = substr($RSdata, 3);
}

$items = explode(",", $items);
$clientID       = $items[0];
$itemTypeID     = $items[1];
$itemID         = $items[2];

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

$results = getPropertiesExtendedForItemAndUser($itemTypeID, $itemID, $clientID, $RSuserID);

// And return XML response back to application
RSreturnArrayQueryResults($results, false);
