<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID  = $GLOBALS['RS_POST']['clientID'];
$docTypeID = $GLOBALS['RS_POST']['docTypeID']; // ID of the financial document type to duplicate
$docID     = $GLOBALS['RS_POST']['docID']; // ID of the document to duplicate

$RSuserID = RScheckUserAccess();

$documentsITID = getClientItemTypeIDRelatedWithByName($definitions['financialDocuments'], $clientID);
$docITID       = getPropertyValue($definitions['financialDocumentsItemTypeID'], $documentsITID, $docTypeID, $clientID);

// Duplicate the document
$newDocID = duplicateItem($docITID, $docID, $clientID);

// If the document is an invoice, clear the dates and ID
if ($docITID == getClientItemTypeIDRelatedWithByName($definitions['invoiceClient'], $clientID)) {
    // Clear the ID
    $invoiceIDPID = getClientPropertyIDRelatedWithByName($definitions['invoiceClientInvoiceID'], $clientID);
    $result = setPropertyValueByID($invoiceIDPID, $docITID, $newDocID, $clientID, '0', '', $RSuserID);

    // Clear the invoice date
    $invoiceDatePID = getClientPropertyIDRelatedWithByName($definitions['invoiceClientInvoiceDate'], $clientID);
    setPropertyValueByID($invoiceDatePID, $docITID, $newDocID, $clientID, '', '', $RSuserID);

    // Clear the direct debit date
    $invoiceDatePID = getClientPropertyIDRelatedWithByName($definitions['invoiceClientDebitDate'], $clientID);
    setPropertyValueByID($invoiceDatePID, $docITID, $newDocID, $clientID, '', '', $RSuserID);

    // Clear the payment date
    $invoiceDatePID = getClientPropertyIDRelatedWithByName($definitions['invoiceClientPaymentDate'], $clientID);
    setPropertyValueByID($invoiceDatePID, $docITID, $newDocID, $clientID, '', '', $RSuserID);

    // Clear the sent date
    $invoiceDatePID = getClientPropertyIDRelatedWithByName($definitions['invoiceClientSentDate'], $clientID);
    setPropertyValueByID($invoiceDatePID, $docITID, $newDocID, $clientID, '', '', $RSuserID);
}

// Clear the related operations
$relatedOperationsPID = getPropertyValue($definitions['financialDocumentsRelatedOperationIDs'], $documentsITID, $docTypeID, $clientID);
setPropertyValueByID($relatedOperationsPID, $docITID, $newDocID, $clientID, '', '', $RSuserID);

// Now we must duplicate the associated concepts, if they exist.
$conceptsITID      = getPropertyValue($definitions['financialDocumentsConceptID'], $documentsITID, $docTypeID, $clientID);
$parentDocumentPID = getPropertyValue($definitions['financialDocumentsConceptFilterCriteria'], $documentsITID, $docTypeID, $clientID);

if ($conceptsITID != '') {
    // Get a list of the associated concepts
    $returnProperties   = array();
    $filterProperties   = array();
    $filterProperties[] = array('ID' => $parentDocumentPID, 'value' => $docID, 'mode' => "=");

    $concepts = getFilteredItemsIDs($conceptsITID, $clientID, $filterProperties, $returnProperties, $orderBy = '', $translateIds = false, $limit = '', $ids = '');

    foreach ($concepts as $concept) {
        // Duplicate the concept
        $newConceptID = duplicateItem($conceptsITID, $concept['ID'], $clientID);

        // Set the parent of the new concept to the new document
        setPropertyValueByID($parentDocumentPID, $conceptsITID, $newConceptID, $clientID, $newDocID, '', $RSuserID);
    }
}

$results['result'] = 'OK';
$results['newDocID'] = $newDocID;

// Return results
RSreturnArrayResults($results);
