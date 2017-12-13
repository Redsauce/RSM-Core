<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get the "result" item type
$results['itemTypeID'] = getClientItemTypeID_RelatedWith_byName($definitions['result'], $clientID);

// get the property
$results['valuePropertyID'] = getClientPropertyID_RelatedWith_byName($definitions['resultValue'], $clientID);

// Return results
RSReturnArrayResults($results);
?>