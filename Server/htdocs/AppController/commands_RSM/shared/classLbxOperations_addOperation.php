<?php
//***********************************************************
//Description:
//  Add an operation
// --> updated for the v.3.10
//***********************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];

// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName('operations', $clientID);

// get some operations properties we will need
$subAccountPropertyID = getClientPropertyID_RelatedWith_byName('operations.subAccountID', $clientID);
$relatedOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);

// create a new operation
$values = array();
$values[] = array('ID' => $subAccountPropertyID, 'value' => $subAccountID);

$operationID = createItem($clientID, $values);

// build results array
$results['ID'] = $operationID;
$results['subAccount'] = translateSingleIdentifier($subAccountPropertyID, $subAccountID, $clientID);
$results['operationID'] = getPropertyValue($definitions['operationOperationID'], $itemTypeID, $operationID, $clientID);
$results['relatedOperations'] = translateMultiIdentifier($relatedOperationsPropertyID, getItemPropertyValue($operationID, $relatedOperationsPropertyID, $clientID), $clientID);
$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $itemTypeID, $operationID, $clientID);
$results['payDate'] = getPropertyValue($definitions['operationPayDate'], $itemTypeID, $operationID, $clientID);
$results['invoiceDate'] = getPropertyValue($definitions['operationInvoiceDate'], $itemTypeID, $operationID, $clientID);
$results['domicilyDate'] = getPropertyValue($definitions['operationDomicilyDate'], $itemTypeID, $operationID, $clientID);
$results['valueDate'] = getPropertyValue($definitions['operationValueDate'], $itemTypeID, $operationID, $clientID);
$results['base'] = getPropertyValue($definitions['operationBase'], $itemTypeID, $operationID, $clientID);
$results['IVA'] = getPropertyValue($definitions['operationIVA'], $itemTypeID, $operationID, $clientID);
$results['deduction'] = getPropertyValue($definitions['operationDeduction'], $itemTypeID, $operationID, $clientID);
$results['total'] = getPropertyValue($definitions['operationTotal'], $itemTypeID, $operationID, $clientID);
$results['description'] = getPropertyValue($definitions['operationDescription'], $itemTypeID, $operationID, $clientID);
$results['payMethod'] = getPropertyValue($definitions['operationPayMethod'], $itemTypeID, $operationID, $clientID);
$results['bankAccount'] = getPropertyValue($definitions['operationBankAccount'], $itemTypeID, $operationID, $clientID);
$results['note'] = getPropertyValue($definitions['operationNote'], $itemTypeID, $operationID, $clientID);
$results['showNote'] = getPropertyValue($definitions['operationShowNote'], $itemTypeID, $operationID, $clientID);
$results['status'] = getPropertyValue($definitions['operationStatus'], $itemTypeID, $operationID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
