<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Retrieve POST variables
isset($GLOBALS[$cstRS_POST][$cstClientID   ]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['filterID'   ]) ? $filterID = $GLOBALS[$cstRS_POST]['filterID'] : dieWithError(400);

if ($filterID == "") $filterID = "0";
 
$results = getFilterProperties($clientID,$filterID);

// And return XML response back to application			
RSReturnArrayQueryResults($results);
?>