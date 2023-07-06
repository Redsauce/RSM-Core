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
$itemTypeID = getClientItemTypeIDRelatedWithByName('operations', $clientID);

// get some operations properties we will need
$subAccountPropertyID = getClientPropertyIDRelatedWithByName('operations.subAccountID', $clientID);
$relatedOperationsPropertyID = getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID);

// create a new operation
$values = array();
$values[] = array('ID' => $subAccountPropertyID, 'value' => $subAccountID);

$operationID = createItem($clientID, $values);

// build results array
$results['ID'] = $operationID;
$results['subAccount'] = translateSingleIdentifier($subAccountPropertyID, $subAccountID, $clientID);
$results['operationID'] = getPropertyValue($definitions['operationOperationID'], $operationID, $clientID);
$results['relatedOperations'] = translateMultiIdentifier($relatedOperationsPropertyID, getItemPropertyValue($operationID, $relatedOperationsPropertyID, $clientID), $clientID);
$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $operationID, $clientID);
$results['payDate'] = getPropertyValue($definitions['operationPayDate'], $operationID, $clientID);
$results['invoiceDate'] = getPropertyValue($definitions['operationInvoiceDate'], $operationID, $clientID);
$results['domicilyDate'] = getPropertyValue($definitions['operationDomicilyDate'], $operationID, $clientID);
$results['valueDate'] = getPropertyValue($definitions['operationValueDate'], $operationID, $clientID);
$results['base'] = getPropertyValue($definitions['operationBase'], $operationID, $clientID);
$results['IVA'] = getPropertyValue($definitions['operationIVA'], $operationID, $clientID);
$results['deduction'] = getPropertyValue($definitions['operationDeduction'], $operationID, $clientID);
$results['total'] = getPropertyValue($definitions['operationTotal'], $operationID, $clientID);
$results['description'] = getPropertyValue($definitions['operationDescription'], $operationID, $clientID);
$results['payMethod'] = getPropertyValue($definitions['operationPayMethod'], $operationID, $clientID);
$results['bankAccount'] = getPropertyValue($definitions['operationBankAccount'], $operationID, $clientID);
$results['note'] = getPropertyValue($definitions['operationNote'], $operationID, $clientID);
$results['showNote'] = getPropertyValue($definitions['operationShowNote'], $operationID, $clientID);
$results['status'] = getPropertyValue($definitions['operationStatus'], $operationID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
