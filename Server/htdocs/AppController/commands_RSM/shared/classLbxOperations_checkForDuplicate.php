<?php
//
//Description:
//	Check for duplicates
// --> updated for the v.3.10
//

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$accountID = $GLOBALS['RS_POST']['accountID'];

// initialize results array
$results['check'] = 'OK';

// get operation item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get operation invoice date property ID
$invoiceDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'], $clientID);

// get operation invoice date
$operationInvoiceDate = getItemPropertyValue($operationID, $invoiceDatePropertyID, $clientID);

if ($operationInvoiceDate == '') {
    // Return OK
    RSReturnArrayResults($results);
    exit ;
}

// save the operation invoice date year
$operationInvoiceDateYear = substr($operationInvoiceDate, 0, 4);

// get accounts and subaccounts item types
$accountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

// retrieve the accountType property ID
$accountTypePropertyID = getClientPropertyID_RelatedWith_byName($definitions['accountType'], $clientID);

// retrieve the account type
$accountType = getItemPropertyValue($accountID, $accountTypePropertyID, $clientID);

// the account may be a "part" of a more general account (example, accounts 431 and 432 are a part of the account 43...), so we have to retrieve this account
$filterProperties = array();
$filterProperties[] = array('ID' => $accountTypePropertyID, 'value' => substr($accountType, 0, 2) . '%', 'mode' => 'LIKE');

$accountsQueryResults = IQ_getFilteredItemsIDs($accountsItemTypeID, $clientID, $filterProperties, array());

$accounts = array();
while ($row = $accountsQueryResults->fetch_assoc()) {
    $accounts[] = $row['ID'];
}

// get subaccounts pertaining to these accounts
$filterProperties = array();
if (count($accounts) > 1) {
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => implode(',', $accounts), 'mode' => '<-IN');
} else
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accounts[0]);

$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

$subAccounts = array();
while ($row = $subAccountsQueryResults->fetch_assoc())
    $subAccounts[] = $row['ID'];

if (count($subAccounts) > 0) {

    // get subAccountID property ID
    $subAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID);

    // get operation subAccountID
    $operationSubAccountID = getItemPropertyValue($operationID, $subAccountPropertyID, $clientID);

    /*
     if (in_array($operationSubAccountID, $subAccounts)) {
     // Return OK
     $results['check'] = 'OK';

     RSReturnArrayResults($results);
     exit;
     }
     */

    // get operationID property ID
    $operationIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'], $clientID);

    // get operation operationID
    $operationOperationID = getItemPropertyValue($operationID, $operationIDPropertyID, $clientID);

    if ($operationOperationID != '0') {
        // build filter properties array
        $filterProperties = array();
        if (count($subAccounts) > 1) {
            $filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
        } else {
            $filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => $subAccounts[0]);
        }
        $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => ($operationInvoiceDateYear - 1) . '-12-31', 'mode' => 'AFTER');
        $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => ($operationInvoiceDateYear + 1) . '-01-01', 'mode' => 'BEFORE');
        $filterProperties[] = array('ID' => $operationIDPropertyID, 'value' => $operationOperationID);

        // get current year's operations for the account
        $operationsQuery = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, array());

        if ($operationsQuery->num_rows > 0)
            while ($row = $operationsQuery->fetch_assoc())
                if ($row['ID'] != $operationID) {
                    $results['check'] = 'NOK';
                    $results['duplicateID'] = $operationOperationID;
                    break;
                }
    }
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>