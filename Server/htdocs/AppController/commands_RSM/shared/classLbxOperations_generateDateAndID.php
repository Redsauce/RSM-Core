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
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$accountID = $GLOBALS[$cstRS_POST]['accountID'];
$operationID = $GLOBALS[$cstRS_POST]['operationID'];

// get the item types
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);
// operations
$accountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);
// accounts
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
// subaccounts

// retrieve the account type
$accountType = getPropertyValue($definitions['accountType'], $accountID, $clientID);

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

$date = date('Y-m-d');
$subAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID);
$yearScope = array('propertyID' => $invoiceDatePropertyID, 'type' => getPropertyType($invoiceDatePropertyID, $clientID), 'year' => intval(substr($date, 0, 4)));
$seriesScope = array('propertyID' => $subAccountPropertyID, 'type' => getPropertyType($subAccountPropertyID, $clientID), 'values' => $subAccounts);
$nextID = RSallocateNextIntegerPropertyValue($clientID, $itemTypeID, $operationIDPropertyID,
    function ($next) use ($clientID, $itemTypeID, $operationID, $operationIDPropertyID, $invoiceDatePropertyID, $date, $RSuserID) {
        // Keep this command's existing number/date rules inside the allocation lock.
        if (getItemPropertyValue($operationID, $operationIDPropertyID, $clientID) > 0
            || getItemPropertyValue($operationID, $invoiceDatePropertyID, $clientID) != '') return false;
        if (setPropertyValueByID($operationIDPropertyID, $itemTypeID, $operationID, $clientID, $next, '', $RSuserID) !== 0) return false;
        return setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $operationID, $clientID, $date, '', $RSuserID) === 0;
    }, $yearScope, $seriesScope);
if ($nextID === false) {
    $results['result'] = 'NOK';
    $results['description'] = 'ERROR ASSIGNING NUMBER AND DATE';
    RSReturnArrayResults($results);
    exit;
}

$results['result'] = 'OK';
$results['ID'] = $operationID;
$results['operationID'] = getItemPropertyValue($operationID, $operationIDPropertyID, $clientID);
$results['invoiceDate'] = getItemPropertyValue($operationID, $invoiceDatePropertyID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
