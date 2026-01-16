<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $clientID   = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemID'    ]) ? $itemID     = $GLOBALS[$cstRS_POST]['itemID'    ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyID]) ? $propertyID = $GLOBALS[$cstRS_POST][$cstPropertyID] : dieWithError(400);

$propertyID = parsePID($propertyID, $clientID);

// First, check if the user has access to this property
if (!isPropertyVisible($RSuserID, $propertyID, $clientID)) {
   // There were no permissions
   $results = array('propertyId' => '-1');
   RSReturnArrayQueryResults($results);
}

$results = getAuditTrail($clientID, $propertyID, $itemID);

// And return XML response back to application
RSReturnArrayQueryResults($results);
