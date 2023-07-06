<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "./api_headers.php";

// Obtain the data needed by this script
isset($GLOBALS['RS_POST']['clientID'  ]) ? $itemID     = $GLOBALS['RS_POST']['clientID'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID']) ? $itemTypeID = $GLOBALS['RS_POST']['itemTypeID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemID'    ]) ? $itemID     = $GLOBALS['RS_POST']['itemID'    ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken'   ]) ? $RStoken    = $GLOBALS['RS_POST']['RStoken'   ] : $RStoken  = '';

if (!isset($RSuserID)) {
  $RSuserID =  0;
}

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

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
RSReturnArrayQueryResults($results, false);
