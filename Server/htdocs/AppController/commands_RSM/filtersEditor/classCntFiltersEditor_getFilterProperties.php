<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Retrieve POST variables
isset($GLOBALS['RS_POST']['clientID'   ]) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['filterID'   ]) ? $filterID = $GLOBALS['RS_POST']['filterID'] : dieWithError(400);

if ($filterID == "") {
    $filterID = "0";
}

$results = getFilterProperties($clientID, $filterID);

// And return XML response back to application
RSReturnArrayQueryResults($results);
