<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$accountID = $GLOBALS['RS_POST']['accountID'];  // this one can be one account ID or a list of accounts separated by coma

// get the subaccounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

// get the main property and the account property
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$accountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID);

// build the filter properties array
$filterProperties = array();
if (strpos($accountID, ',') === false) {
	// filter by account ID
	$filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID);
} else {
	// filter by accounts IDs
	$filterProperties[] = array('ID' => $accountPropertyID, 'value' => $accountID, 'mode' => '<-IN');
}

// build the return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');

// get the subaccounts
$subAccounts = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

// Return results
RSReturnQueryResults($subAccounts);
?>