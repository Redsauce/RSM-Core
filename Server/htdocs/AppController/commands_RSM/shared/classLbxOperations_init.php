<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID']  : dieWithError(400);
isset($GLOBALS['RS_POST']['userID']) ? $userID   = $GLOBALS['RS_POST']['userID']  : dieWithError(400);

// get operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName('operations', $clientID);

// get operations properties IDs
$subAccountPropertyID        = getClientPropertyID_RelatedWith_byName('operations.subAccountID', $clientID);
$operationPropertyID         = getClientPropertyID_RelatedWith_byName('operations.operationID', $clientID);
$sendDatePropertyID          = getClientPropertyID_RelatedWith_byName('operations.sendDate', $clientID);
$payDatePropertyID           = getClientPropertyID_RelatedWith_byName('operations.payDate', $clientID);
$invoiceDatePropertyID       = getClientPropertyID_RelatedWith_byName('operations.invoiceDate', $clientID);
$valueDatePropertyID         = getClientPropertyID_RelatedWith_byName('operations.valueDate', $clientID);
$domicilyDatePropertyID      = getClientPropertyID_RelatedWith_byName('operations.domicilyDate', $clientID);
$basePropertyID              = getClientPropertyID_RelatedWith_byName('operations.base', $clientID);
$IVAPropertyID               = getClientPropertyID_RelatedWith_byName('operations.IVA', $clientID);
$deductionPropertyID         = getClientPropertyID_RelatedWith_byName('operations.deduction', $clientID);
$totalPropertyID             = getClientPropertyID_RelatedWith_byName('operations.total', $clientID);
$descriptionPropertyID       = getClientPropertyID_RelatedWith_byName('operations.description', $clientID);
$payMethodPropertyID         = getClientPropertyID_RelatedWith_byName('operations.payMethod', $clientID);
$bankAccountPropertyID       = getClientPropertyID_RelatedWith_byName('operations.bankAccount', $clientID);
$notePropertyID              = getClientPropertyID_RelatedWith_byName('operations.note', $clientID);
$showNotePropertyID          = getClientPropertyID_RelatedWith_byName('operations.showNote', $clientID);
$relatedOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);
$statusPropertyID            = getClientPropertyID_RelatedWith_byName('operations.status', $clientID);

// get operations properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);

if (in_array($subAccountPropertyID, $propertiesAllowed)) {
    $subAccountAllowed = '1';
} else {
    $subAccountAllowed = '0';
}
if (in_array($operationPropertyID, $propertiesAllowed)) {
    $operationAllowed = '1';
} else {
    $operationAllowed = '0';
}
if (in_array($sendDatePropertyID, $propertiesAllowed)) {
    $sendDateAllowed = '1';
} else {
    $sendDateAllowed = '0';
}
if (in_array($payDatePropertyID, $propertiesAllowed)) {
    $payDateAllowed = '1';
} else {
    $payDateAllowed = '0';
}
if (in_array($invoiceDatePropertyID, $propertiesAllowed)) {
    $invoiceDateAllowed = '1';
} else {
    $invoiceDateAllowed = '0';
}
if (in_array($valueDatePropertyID, $propertiesAllowed)) {
    $valueDateAllowed = '1';
} else {
    $valueDateAllowed = '0';
}
if (in_array($domicilyDatePropertyID, $propertiesAllowed)) {
    $domicilyDateAllowed = '1';
} else {
    $domicilyDateAllowed = '0';
}
if (in_array($basePropertyID, $propertiesAllowed)) {
    $baseAllowed = '1';
} else {
    $baseAllowed = '0';
}
if (in_array($IVAPropertyID, $propertiesAllowed)) {
    $IVAAllowed = '1';
} else {
    $IVAAllowed = '0';
}
if (in_array($deductionPropertyID, $propertiesAllowed)) {
    $deductionAllowed = '1';
} else {
    $deductionAllowed = '0';
}
if (in_array($totalPropertyID, $propertiesAllowed)) {
    $totalAllowed = '1';
} else {
    $totalAllowed = '0';
}
if (in_array($descriptionPropertyID, $propertiesAllowed)) {
    $descriptionAllowed = '1';
} else {
    $descriptionAllowed = '0';
}
if (in_array($payMethodPropertyID, $propertiesAllowed)) {
    $payMethodAllowed = '1';
} else {
    $payMethodAllowed = '0';
}
if (in_array($bankAccountPropertyID, $propertiesAllowed)) {
    $bankAccountAllowed = '1';
} else {
    $bankAccountAllowed = '0';
}
if (in_array($notePropertyID, $propertiesAllowed)) {
    $noteAllowed = '1';
} else {
    $noteAllowed = '0';
}
if (in_array($showNotePropertyID, $propertiesAllowed)) {
    $showNoteAllowed = '1';
} else {
    $showNoteAllowed = '0';
}
if (in_array($relatedOperationsPropertyID, $propertiesAllowed)) {
    $relatedOperationsAllowed = '1';
} else {
    $relatedOperationsAllowed = '0';
}
if (in_array($statusPropertyID, $propertiesAllowed)) {
    $statusAllowed = '1';
} else {
    $statusAllowed = '0';
}

$results = array();

// Se cambia el gestor de variables globales de forma que nos permita devolver
// diferentes variables globales que representen diferentes previews de una factura en función de su URL
$theQuery = 'SELECT RS_NAME,RS_VALUE FROM rs_globals WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_NAME LIKE "invoicer.%"';

$GlobalInvoicerResult = RSquery($theQuery);

if ($GlobalInvoicerResult) {
    while ($row = $GlobalInvoicerResult->fetch_assoc()) {
        $results[$row["RS_NAME"]] = $row["RS_VALUE"];
    }
}

// return the item type
$results['itemTypeID'] = $itemTypeID;

// return the operation status
$results['openStatus'] = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusOpen'), $clientID), $clientID);
$results['closedStatus'] = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusClosed'), $clientID), $clientID);


// return the columns names
$results['SubAccountID'] = getClientPropertyName($subAccountPropertyID, $clientID) . '::' . $subAccountAllowed;  // fix me: separator used -> ::
$results['OperationID'] = getClientPropertyName($operationPropertyID, $clientID) . '::' . $operationAllowed;
$results['SendDate'] = getClientPropertyName($sendDatePropertyID, $clientID) . '::' . $sendDateAllowed;
$results['PayDate'] = getClientPropertyName($payDatePropertyID, $clientID) . '::' . $payDateAllowed;
$results['ValueDate'] = getClientPropertyName($valueDatePropertyID, $clientID) . '::' . $valueDateAllowed;
$results['InvoiceDate'] = getClientPropertyName($invoiceDatePropertyID, $clientID) . '::' . $invoiceDateAllowed;
$results['DomicilyDate'] = getClientPropertyName($domicilyDatePropertyID, $clientID) . '::' . $domicilyDateAllowed;
$results['Base'] = getClientPropertyName($basePropertyID, $clientID) . '::' . $baseAllowed;
$results['IVA'] = getClientPropertyName($IVAPropertyID, $clientID) . '::' . $IVAAllowed;
$results['Deduction'] = getClientPropertyName($deductionPropertyID, $clientID) . '::' . $deductionAllowed;
$results['Total'] = getClientPropertyName($totalPropertyID, $clientID) . '::' . $totalAllowed;
$results['Description'] = getClientPropertyName($descriptionPropertyID, $clientID) . '::' . $descriptionAllowed;
$results['PayMethod'] = getClientPropertyName($payMethodPropertyID, $clientID) . '::' . $payMethodAllowed;
$results['BankAccount'] = getClientPropertyName($bankAccountPropertyID, $clientID) . '::' . $bankAccountAllowed;
$results['Note'] = getClientPropertyName($notePropertyID, $clientID) . '::' . $noteAllowed;
$results['ShowNote'] = getClientPropertyName($showNotePropertyID, $clientID) . '::' . $showNoteAllowed;
$results['RelatedOperations'] = getClientPropertyName($relatedOperationsPropertyID, $clientID) . '::' . $relatedOperationsAllowed;
$results['Status'] = getClientPropertyName($statusPropertyID, $clientID) . '::' . $statusAllowed;

// And write XML Response back to the application
RSreturnArrayResults($results);
