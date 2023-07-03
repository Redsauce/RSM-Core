<?php
//***********************************************************
//Description:
//  Add an operation
// --> updated for the v.3.10
//***********************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];
$date = $GLOBALS['RS_POST']['date'];
$transactionID = $GLOBALS['RS_POST']['transactionID'];
$amount = base64_decode($GLOBALS['RS_POST']['amount']);
$VAT = base64_decode($GLOBALS['RS_POST']['VAT']);
$deduction = base64_decode($GLOBALS['RS_POST']['deduction']);
$description = base64_decode($GLOBALS['RS_POST']['description']);

// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get some operations properties we will need
$subAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID);
$relatedOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);
$invoiceDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'], $clientID);
$payDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationPayDate'], $clientID);
$basePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationBase'], $clientID);
$totalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationTotal'], $clientID);
$VATPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationIVA'], $clientID);
$deductionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationDeduction'], $clientID);
$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationDescription'], $clientID);

$propertiesValues = array(array('ID' => $subAccountPropertyID, 'value' => $subAccountID), array('ID' => $invoiceDatePropertyID, 'value' => $date), array('ID' => $payDatePropertyID, 'value' => $date), array('ID' => $basePropertyID, 'value' => $amount - $VAT + $deduction), array('ID' => $deductionPropertyID, 'value' => $deduction), array('ID' => $totalPropertyID, 'value' => $amount), array('ID' => $VATPropertyID, 'value' => $VAT), array('ID' => $descriptionPropertyID, 'value' => $description));

// create a new operation
$operationID = createItem($clientID, $propertiesValues);

// the operation pertains to the subaccount passed, so update the property value
//setPropertyValueByID($subAccountPropertyID, $itemTypeID, $operationID, $clientID, $subAccountID, '', $RSuserID);
//setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $operationID, $clientID, $date, '', $RSuserID);
//setPropertyValueByID($payDatePropertyID, $itemTypeID, $operationID, $clientID, $date, '', $RSuserID);
//setPropertyValueByID($totalPropertyID, $itemTypeID, $operationID, $clientID, $amount, '', $RSuserID);
//setPropertyValueByID($VATPropertyID, $itemTypeID, $operationID, $clientID, $VAT, '', $RSuserID);
//setPropertyValueByID($descriptionPropertyID, $itemTypeID, $operationID, $clientID, $description, '', $RSuserID);

// --- RELATIONSHIP ---
// add operations into the properly lists
addIdentifier($operationID, $itemTypeID, $transactionID, $relatedOperationsPropertyID, $clientID, $RSuserID);
addIdentifier($transactionID, $itemTypeID, $operationID, $relatedOperationsPropertyID, $clientID, $RSuserID);

// --- STATUS ---
// get related transaction total
$transactionTotal = getItemPropertyValue($transactionID, $totalPropertyID, $clientID);

// get related operations
$transactionRelatedOperations = explode(',', getItemPropertyValue($transactionID, $relatedOperationsPropertyID, $clientID));

$total = 0;
foreach ($transactionRelatedOperations as $operation) {
    $total += getItemPropertyValue($operation, $totalPropertyID, $clientID);
}

if ($total == abs($transactionTotal)) {
    // get the closed status
    $closedStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusClosed'), $clientID), $clientID);

    // close operation
    setItemPropertyValue($definitions['operationStatus'], $itemTypeID, $transactionID, $clientID, $closedStatus, $RSuserID);

    // return close: yes
    $results['close'] = 'yes';
} else {

    // return close: no
    $results['close'] = 'no';
}

// build results array
$results['ID'] = $operationID;
$results['subAccount'] = translateSingleIdentifier($subAccountPropertyID, $subAccountID, $clientID);
$results['relatedOperations'] = translateMultiIdentifier($relatedOperationsPropertyID, getItemPropertyValue($operationID, $relatedOperationsPropertyID, $clientID), $clientID);

$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $itemTypeID, $operationID, $clientID);
$results['operationID'] = getPropertyValue($definitions['operationOperationID'], $itemTypeID, $operationID, $clientID);
$results['payDate'] = getPropertyValue($definitions['operationPayDate'], $itemTypeID, $operationID, $clientID);
$results['valueDate'] = getPropertyValue($definitions['operationValueDate'], $itemTypeID, $operationID, $clientID);
$results['invoiceDate'] = getPropertyValue($definitions['operationInvoiceDate'], $itemTypeID, $operationID, $clientID);
$results['domicilyDate'] = getPropertyValue($definitions['operationDomicilyDate'], $itemTypeID, $operationID, $clientID);
$results['base'] = getPropertyValue($definitions['operationBase'], $itemTypeID, $operationID, $clientID);
$results['deduction'] = getPropertyValue($definitions['operationDeduction'], $itemTypeID, $operationID, $clientID);
$results['payMethod'] = getPropertyValue($definitions['operationPayMethod'], $itemTypeID, $operationID, $clientID);
$results['bankAccount'] = getPropertyValue($definitions['operationBankAccount'], $itemTypeID, $operationID, $clientID);
$results['note'] = getPropertyValue($definitions['operationNote'], $itemTypeID, $operationID, $clientID);

$results['showNote'] = getPropertyValue($definitions['operationShowNote'], $itemTypeID, $operationID, $clientID);
$results['status'] = getPropertyValue($definitions['operationStatus'], $itemTypeID, $operationID, $clientID);
$results['IVA'] = getItemPropertyValue($operationID, $VATPropertyID, $clientID);
$results['total'] = getItemPropertyValue($operationID, $totalPropertyID, $clientID);
$results['description'] = getItemPropertyValue($operationID, $descriptionPropertyID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
