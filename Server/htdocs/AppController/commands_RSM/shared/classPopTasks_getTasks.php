<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get the item type and the main value
$itemTypeID = getClientItemTypeID_RelatedWith(getAppItemTypeIDByName($definitions['tasks']), $clientID);

// get tasks list
$results = IQ_getItems($itemTypeID, $clientID);

// And return XML response back to the application
RSreturnQueryResults($results);
