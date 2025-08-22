<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// Check the variables
isset($GLOBALS['RS_POST']['clientID'  ]) ? $clientID   = $GLOBALS['RS_POST']['clientID'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemID'    ]) ? $itemID     = $GLOBALS['RS_POST']['itemID'    ] : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyID']) ? $propertyID = $GLOBALS['RS_POST']['propertyID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken'   ]) ? $RStoken    = $GLOBALS['RS_POST']['RStoken'   ] : $RStoken = "";

$results = array();

// Check if the token or the user have access to the requested propertyID
if ((!RShasREADTokenPermission($RStoken, $propertyID)) && (!isPropertyVisible($RSuserID, $propertyID, $clientID))) {
  $results['result'] = 'NOK';
  $results['description'] = 'THIS TOKEN DOES NOT HAVE PERMISSIONS TO AUDIT THIS ITEM';
  RSReturnArrayQueryResults($results);
}

// Process response
$results = getAuditTrail($clientID, $propertyID, $itemID);

// And return XML response back to application without compression
RSReturnArrayQueryResults($results, false);
?>
