<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Get the parameters to work with
isset($GLOBALS['RS_POST']['clientID'  ]) ? $clientID   = $GLOBALS['RS_POST']['clientID'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID']) ? $itemTypeID = $GLOBALS['RS_POST']['itemTypeID'] : dieWithError(400);

// get items
$data = IQ_getItems($itemTypeID, $clientID);

// check response
if (!$data) {
    // The passed itemTypeID could not be found
    $results = array();
    RSReturnArrayResults($results);
} else {
    // Return data
    RSReturnQueryResults($data);
}
