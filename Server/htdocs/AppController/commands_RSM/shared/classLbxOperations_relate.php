<?php
//***************************************************
//Description:
//  Create a relationship between two operations
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID       = $GLOBALS['RS_POST']['clientID'];
$operation_1    = $GLOBALS['RS_POST']['operation_1'];  // a bank statement
$operation_2    = $GLOBALS['RS_POST']['operation_2'];  // an invoice
$setPayDate     = $GLOBALS['RS_POST']['setPayDate'];

// get operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get some properties we will need
$relOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);
$totalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationTotal'], $clientID);
$totalPropertyType = getPropertyType($totalPropertyID, $clientID);

// --- RELATIONSHIP ---
// add operations into the properly lists
addIdentifier($operation_2, $itemTypeID, $operation_1, $relOperationsPropertyID, $clientID, $RSuserID);
addIdentifier($operation_1, $itemTypeID, $operation_2, $relOperationsPropertyID, $clientID, $RSuserID);

// --- STATUS ---
// get operation_1 total
$op1Total = getItemPropertyValue($operation_1, $totalPropertyID, $clientID, $totalPropertyType);

// get operation_1 related operations
$op1RelatedOperations = explode(',', getItemPropertyValue($operation_1, $relOperationsPropertyID, $clientID));

$total = 0;
foreach ($op1RelatedOperations as $operation) {
    $total += getItemPropertyValue($operation, $totalPropertyID, $clientID, $totalPropertyType);
}

if ($total == $op1Total) {
    // get the closed status
    $closedStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusClosed'), $clientID), $clientID);

    // close operation_1
    setItemPropertyValue($definitions['operationStatus'], $itemTypeID, $operation_1, $clientID, $closedStatus, $RSuserID);

    // return close: yes
    $results['close1'] = 'yes';
} else {

    // return close: no
    $results['close1'] = 'no';
}




// --- PAY DATE ---
$results['setPayDate'] = $setPayDate;

if ($setPayDate == '1') {
    // get invoice date property ID
    $invoiceDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'], $clientID);

    // get operation_1 invoice date
    $invoiceDate = getItemPropertyValue($operation_1, $invoiceDatePropertyID, $clientID);

    // set operation_2 pay date to the operation_1 invoice date
    setItemPropertyValue($definitions['operationPayDate'], $itemTypeID, $operation_2, $clientID, $invoiceDate, $RSuserID);

    // return result to the client
    $results['payDate'] = $invoiceDate;
}


// And write XML response back to the application
RSreturnArrayResults($results);
