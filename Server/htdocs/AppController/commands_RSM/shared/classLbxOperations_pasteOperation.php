<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];
$duplicate = $GLOBALS['RS_POST']['duplicate'];

// get item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);

if ($duplicate == 'yes') {
    // duplicate operation
    $newOperationID = duplicateItem($itemTypeID, $operationID, $clientID);

    // change some properties values
    if ($subAccountID != '0') {
        // the operation pertains to the subaccount passed
        setItemPropertyValue($definitions['operationSubAccountID'], $itemTypeID, $newOperationID, $clientID, $subAccountID, $RSuserID);
    }

    // reset operationID
    setItemPropertyValue($definitions['operationOperationID'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationOperationID'], $clientID), $clientID), $RSuserID);

    // reset related operations
    setItemPropertyValue('operations.relatedOperations', $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID), $clientID), $RSuserID);

    // reset dates
    setItemPropertyValue($definitions['operationSendDate'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationSendDate'], $clientID), $clientID), $RSuserID);
    setItemPropertyValue($definitions['operationPayDate'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationPayDate'], $clientID), $clientID), $RSuserID);
    setItemPropertyValue($definitions['operationInvoiceDate'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationInvoiceDate'], $clientID), $clientID), $RSuserID);
    setItemPropertyValue($definitions['operationDomicilyDate'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationDomicilyDate'], $clientID), $clientID), $RSuserID);
    setItemPropertyValue($definitions['operationValueDate'], $itemTypeID, $newOperationID, $clientID, getClientPropertyDefaultValue(getClientPropertyIDRelatedWithByName($definitions['operationValueDate'], $clientID), $clientID), $RSuserID);

    // duplicate concepts
    $conceptsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['concepts'], $clientID);

    // build filter properties array
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['conceptOperationID'], $clientID), 'value' => $operationID);

    // get invoice concepts
    $concepts = iqGetFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, array());

    // duplicate
    while ($row = $concepts->fetch_assoc()) {
        $newConceptID = duplicateItem($conceptsItemTypeID, $row['ID'], $clientID);
        // the concept pertains to the duplicated operation
        setItemPropertyValue($definitions['conceptOperationID'], $conceptsItemTypeID, $newConceptID, $clientID, $newOperationID, $RSuserID);
    }
} else {

    // simply change the subAccountID to the operation
    $newOperationID = $operationID;

    setItemPropertyValue($definitions['operationSubAccountID'], $itemTypeID, $newOperationID, $clientID, $subAccountID, $RSuserID);
}


$results['ID'] = $newOperationID;
$results['subAccount'] = translateSingleIdentifier(getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID), getPropertyValue($definitions['operationSubAccountID'], $newOperationID, $clientID), $clientID);
$results['operationID'] = getPropertyValue($definitions['operationOperationID'], $newOperationID, $clientID);
$results['relatedOperations'] = translateMultiIdentifier(getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID), getPropertyValue('operations.relatedOperations', $newOperationID, $clientID), $clientID);
$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $newOperationID, $clientID);
$results['payDate'] = getPropertyValue($definitions['operationPayDate'], $newOperationID, $clientID);
$results['invoiceDate'] = getPropertyValue($definitions['operationInvoiceDate'], $newOperationID, $clientID);
$results['domicilyDate'] = getPropertyValue($definitions['operationDomicilyDate'], $newOperationID, $clientID);
$results['valueDate'] = getPropertyValue($definitions['valueDomicilyDate'], $newOperationID, $clientID);
$results['base'] = getPropertyValue($definitions['operationBase'], $newOperationID, $clientID);
$results['IVA'] = getPropertyValue($definitions['operationIVA'], $newOperationID, $clientID);
$results['deduction'] = getPropertyValue($definitions['operationDeduction'], $newOperationID, $clientID);
$results['total'] = getPropertyValue($definitions['operationTotal'], $newOperationID, $clientID);
$results['description'] = getPropertyValue($definitions['operationDescription'], $newOperationID, $clientID);
$results['payMethod'] = getPropertyValue($definitions['operationPayMethod'], $newOperationID, $clientID);
$results['bankAccount'] = getPropertyValue($definitions['operationBankAccount'], $newOperationID, $clientID);
$results['note'] = getPropertyValue($definitions['operationNote'], $newOperationID, $clientID);
$results['showNote'] = getPropertyValue($definitions['operationShowNote'], $newOperationID, $clientID);
$results['status'] = getPropertyValue($definitions['operationStatus'], $newOperationID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
