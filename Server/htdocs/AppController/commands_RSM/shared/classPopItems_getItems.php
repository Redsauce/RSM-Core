<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$results = IQ_getItems($itemTypeID, $clientID);

// Return data
RSReturnQueryResults($results);
