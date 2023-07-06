<?php
//***********************************************************
//Description:
//  Edit an invoice
// --> updated for the v.3.10
//***********************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);

// get the operation identifiers properties
$subAccountPropertyID = getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID);
$relatedOperationsPropertyID = getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID);

$results['ID'] = $operationID;
$results['subAccount'] = translateSingleIdentifier($subAccountPropertyID, getItemPropertyValue($operationID, $subAccountPropertyID, $clientID), $clientID);
$results['operationID'] = getPropertyValue($definitions['operationOperationID'], $itemTypeID, $operationID, $clientID);
$results['relatedOperations'] = translateMultiIdentifier($relatedOperationsPropertyID, getItemPropertyValue($operationID, $relatedOperationsPropertyID, $clientID), $clientID);
$results['valueDate'] = getPropertyValue($definitions['operationValueDate'], $itemTypeID, $operationID, $clientID);
$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $itemTypeID, $operationID, $clientID);
$results['payDate'] = getPropertyValue($definitions['operationPayDate'], $itemTypeID, $operationID, $clientID);
$results['invoiceDate'] = getPropertyValue($definitions['operationInvoiceDate'], $itemTypeID, $operationID, $clientID);
$results['domicilyDate'] = getPropertyValue($definitions['operationDomicilyDate'], $itemTypeID, $operationID, $clientID);
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
RSreturnArrayResults($results);
