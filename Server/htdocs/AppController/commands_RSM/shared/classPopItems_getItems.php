<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];

$results = IQ_getItems($itemTypeID, $clientID);
	
// Return data			
RSReturnQueryResults($results);
