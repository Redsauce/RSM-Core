<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$accountID = $GLOBALS['RS_POST']['accountID'];


// get the subaccounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

// get the subaccounts accountID property ID
$accountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID);
$personalIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountPersonalID'], $clientID);




// calculate the personal ID (the max ID for the subaccounts pertaining to the current account + 1)
$maxID = 0;

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID);

$returnProperties = array();
$returnProperties[] = array('ID' => $personalIDPropertyID, 'name' => 'personalID');

// get subaccounts
$subAccountsQueryResults = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

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
$results['mainValue'] = getClientItemMainPropertyValue($newSubAccountID, $itemTypeID, $clientID);
$results['personalID'] = getPropertyValue($definitions['subAccountPersonalID'], $itemTypeID, $newSubAccountID, $clientID);

// Return results
RSreturnArrayResults($results);
