<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// Check the variables
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $clientID   = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemID'    ]) ? $itemID     = $GLOBALS[$cstRS_POST]['itemID'    ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyID]) ? $propertyID = $GLOBALS[$cstRS_POST][$cstPropertyID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken    = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken = "";

$results = array();

// Check if the token or the user have access to the requested propertyID
if ((!RShasREADTokenPermission($RStoken, $propertyID)) && (!isPropertyVisible($RSuserID, $propertyID, $clientID))) {
  $results['result'] = 'NOK';
  $results['description'] = 'THIS TOKEN DOES NOT HAVE PERMISSIONS TO AUDIT THIS ITEM';
  RSReturnArrayQueryResults($results);
}

$itemTypeID = getItemTypeIDFromProperties(array($propertyID), $clientID);
if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemID)) {
  $results['result'] = 'NOK';
  $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THIS ITEM';
  RSReturnArrayQueryResults($results);
}

// Process response
$results = getAuditTrail($clientID, $propertyID, $itemID);

// And return XML response back to application without compression
RSReturnArrayQueryResults($results, false);
