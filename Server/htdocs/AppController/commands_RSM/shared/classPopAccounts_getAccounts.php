<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];

// get the accounts item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['accounts'], $clientID);

$accounts = iqGetItems($itemTypeID, $clientID);

// Return results
RSreturnQueryResults($accounts);
