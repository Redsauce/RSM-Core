<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Get the parameters to work with
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $clientID   = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemTypeID']) ? $itemTypeID = $GLOBALS[$cstRS_POST]['itemTypeID'] : dieWithError(400);

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
?>