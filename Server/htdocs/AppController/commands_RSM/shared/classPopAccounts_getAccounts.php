<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];

// get the accounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);

$accounts = IQ_getItems($itemTypeID, $clientID);

// Return results
RSReturnQueryResults($accounts);
?>