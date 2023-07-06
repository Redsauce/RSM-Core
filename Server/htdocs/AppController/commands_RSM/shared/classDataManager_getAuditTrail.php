<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID   = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemID']) ? $itemID     = $GLOBALS['RS_POST']['itemID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyID']) ? $propertyID = $GLOBALS['RS_POST']['propertyID'] : dieWithError(400);

// First, check if the user has access to this property
if (!isPropertyVisible($RSuserID, $propertyID, $clientID)) {
  // There were no permissions
  $results = array('propertyId' => '-1');
  RSreturnArrayQueryResults($results);
}

$results = getAuditTrail($clientID, $propertyID, $itemID);

// And return XML response back to application
RSreturnArrayQueryResults($results);
