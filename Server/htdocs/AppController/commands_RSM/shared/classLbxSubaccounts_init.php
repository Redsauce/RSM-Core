<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get subaccounts item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['subAccounts'], $clientID);


// return the item type
$results['itemTypeID'] = $itemTypeID;

// return the columns names
$results['mainValue'] = getClientPropertyName(getMainPropertyID($itemTypeID, $clientID), $clientID);
$results['personalID'] = getClientPropertyName(getClientPropertyIDRelatedWithByName($definitions['subAccountPersonalID'], $clientID), $clientID);

// Return results
RSreturnArrayResults($results);
