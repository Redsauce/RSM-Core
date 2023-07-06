<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$accountID = $GLOBALS['RS_POST']['accountID'];  // this one can be one account ID or a list of accounts separated by coma

// get the subaccounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

$accountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);


// get the main property and the account property
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$accountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID);
$personalIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountPersonalID'], $clientID);

$accountTypePropertyID = getClientPropertyID_RelatedWith_byName($definitions['accountType'], $clientID);

// get all accounts with type
$filterProperties = array();
$returnProperties = array();
$returnProperties[] = array('ID' => $accountTypePropertyID, 'name' => 'accountType');
$accounts = getFilteredItemsIDs($accountsItemTypeID, $clientID, $filterProperties, $returnProperties);

// build the filter properties array
$filterProperties = array();

// if no accountID received, return all subaccounts
if ($accountID != "" && $accountID != 0) {
    if (strpos($accountID, ',') === false) {
        // filter by account ID
        $filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID);
    } else {
        // filter by accounts IDs
        $filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID, 'mode' => '<-IN');
    }
}

// build the return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
$returnProperties[] = array('ID' => $personalIDPropertyID, 'name' => 'personalID');
$returnProperties[] = array('ID' => $accountPropertyID, 'name' => 'accountID');

// get the subaccounts
$subAccounts = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

// add accountType property to subAccounts list
for ($i = 0; $i < count($subAccounts); $i++) {
    $j = arraySearchID($subAccounts[$i]['accountID'], $accounts);
    if ($j !== false) {
        $subAccounts[$i]['accountType'] = $accounts[$j]['accountType'];
    }
}

// Return results
RSreturnArrayQueryResults($subAccounts);
