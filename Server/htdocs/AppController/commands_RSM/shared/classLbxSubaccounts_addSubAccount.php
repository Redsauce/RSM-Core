<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$accountID = $GLOBALS['RS_POST']['accountID'];


// get the subaccounts item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['subAccounts'], $clientID);

// get the subaccounts accountID property ID
$accountPropertyID = getClientPropertyIDRelatedWithByName($definitions['subAccountAccountID'], $clientID);
$personalIDPropertyID = getClientPropertyIDRelatedWithByName($definitions['subAccountPersonalID'], $clientID);




// calculate the personal ID (the max ID for the subaccounts pertaining to the current account + 1)
$maxID = 0;

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID);

$returnProperties = array();
$returnProperties[] = array('ID' => $personalIDPropertyID, 'name' => 'personalID');

// get subaccounts
$subAccountsQueryResults = iqGetFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

while ($row = $subAccountsQueryResults->fetch_assoc()) {
    if ($row['personalID'] > $maxID) {
        // update maxID
        $maxID = $row['personalID'];
    }
}


// now create the new subaccount
$values = array();
$values[] = array('ID' => $accountPropertyID, 'value' => $accountID);
$values[] = array('ID' => $personalIDPropertyID, 'value' => $maxID + 1);

$newSubAccountID = createItem($clientID, $values);

$results['ID'] = $newSubAccountID;
$results['mainValue'] = getMainPropertyValue($newSubAccountID, $itemTypeID, $clientID);
$results['personalID'] = getPropertyValue($definitions['subAccountPersonalID'], $newSubAccountID, $clientID);

// Return results
RSreturnArrayResults($results);
