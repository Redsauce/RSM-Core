<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

header('Access-Control-Allow-Origin: *');

// Obtain the data needed by this script
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $itemID     = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemID'    ]) ? $itemID     = $GLOBALS[$cstRS_POST]['itemID'    ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken    = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken  = '';

if (!isset($RSuserID)) $RSuserID =  0;

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemID)) {
  dieWithError(403);
}

if ($RSuserID > 0) {
  // We have user credentials
  $results = getPropertiesExtendedForItemAndUser($itemTypeID, $itemID, $clientID, $RSuserID);
} elseif ($RStoken != '') {
  // We have token credentials
  $results = getPropertiesExtendedForItemAndToken($itemTypeID, $itemID, $RStoken);
} else {
  // We have no credentials
  dieWithError(400);
}

// And return XML response back to application
RSReturnArrayQueryResults($results,false);
