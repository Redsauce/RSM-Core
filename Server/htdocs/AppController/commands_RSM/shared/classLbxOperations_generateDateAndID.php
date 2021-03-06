<?php
//***************************************************
//Description:
//	Generate date and ID for an operation
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$accountID = $GLOBALS['RS_POST']['accountID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the item types
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);
// operations
$accountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);
// accounts
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
// subaccounts

// retrieve the account type
$accountType = getPropertyValue($definitions['accountType'], $accountsItemTypeID, $accountID, $clientID);

// get some properties we will need
$operationIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'], $clientID);
$invoiceDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'], $clientID);

// check if the operation was already generated
$currentOperationID = getItemPropertyValue($operationID, $operationIDPropertyID, $clientID);
$currentInvoiceDate = getItemPropertyValue($operationID, $invoiceDatePropertyID, $clientID);

if (($currentOperationID > 0) || ($currentInvoiceDate != '')) {
    // operationID or/and invoiceDate already generated
    $results['result'] = 'NOK';

    // Write XML Response back to the application
    RSReturnArrayResults($results);
    exit ;
}

// --- calculate the internal ID the operation will be receive (the max ID for the current year and current account) ---
$maxID = 0;

// the account may be a "part" of a more general account (example, accounts 431 and 432 are a part of the account 43...), so we have to retrieve this account
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['accountType'], $clientID), 'value' => substr($accountType, 0, 2) . '%', 'mode' => 'LIKE');

$accountsQueryResults = IQ_getFilteredItemsIDs($accountsItemTypeID, $clientID, $filterProperties, array());

$accounts = array();
while ($row = $accountsQueryResults->fetch_assoc()) {
    $accounts[] = $row['ID'];
}

$accountsIDs = implode(',', $accounts);

// get subaccounts pertaining to these accounts
$filterProperties = array();
if (strpos($accountsIDs, ',') === false) {
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountsIDs);
} else {
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountsIDs, 'mode' => '<-IN');
}

$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

$subAccounts = array();
while ($row = $subAccountsQueryResults->fetch_assoc()) {
    $subAccounts[] = $row['ID'];
}

if (count($subAccounts) > 0) {
    // get subAccountID property
    $subAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID);

    // build filter properties array
    $filterProperties = array();
    if (count($subAccounts) > 1) {
        $filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
    } else {
        $filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => $subAccounts[0]);
    }
    $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y') - 1) . '-12-31', 'mode' => 'AFTER');
    $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y') + 1) . '-01-01', 'mode' => 'BEFORE');

    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $operationIDPropertyID, 'name' => 'operationID');

    // get current year's operations for the account
    $currentYearOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

    while ($row = $currentYearOperations->fetch_assoc()) {
        if ($row['operationID'] > $maxID) {
            // update maxID
            $maxID = $row['operationID'];
        }
    }
}

// update the operationID property, assigning the max retrieved +1
setPropertyValueByID($operationIDPropertyID, $itemTypeID, $operationID, $clientID, $maxID + 1, '', $RSuserID);

// set operation invoiceDate property to the current date
setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $operationID, $clientID, date('Y-m-d'), '', $RSuserID);

$results['result'] = 'OK';
$results['ID'] = $operationID;
$results['operationID'] = getItemPropertyValue($operationID, $operationIDPropertyID, $clientID);
$results['invoiceDate'] = getItemPropertyValue($operationID, $invoiceDatePropertyID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
?>