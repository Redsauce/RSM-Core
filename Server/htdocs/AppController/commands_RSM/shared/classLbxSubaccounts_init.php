<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get subaccounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);


// return the item type
$results['itemTypeID'] = $itemTypeID;

// return the columns names
$results['mainValue'] = getClientPropertyName(getMainPropertyID($itemTypeID, $clientID), $clientID);
$results['personalID'] = getClientPropertyName(getClientPropertyID_RelatedWith_byName($definitions['subAccountPersonalID'], $clientID), $clientID);

// Return results
RSReturnArrayResults($results);
?>