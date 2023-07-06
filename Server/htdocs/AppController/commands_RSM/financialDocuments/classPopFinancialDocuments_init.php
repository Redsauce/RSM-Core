<?php
// Database connection startup and required files
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);

// get the operations item type
$itemTypeID = getClientItemTypeIDRelatedWithByName('financial.documents', $clientID);

// build return properties array
$returnProperties = array();

$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.itemTypeID', $clientID), 'name' => 'itemTypeID', 'trName' => 'itemTypeName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.subItemTypeID', $clientID), 'name' => 'subItemTypeID', 'trName' => 'subItemTypeName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.filterCriteria', $clientID), 'name' => 'filterCriteriaID', 'trName' => 'filterCriteriaName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.previewURL', $clientID), 'name' => 'previewURL', 'trName' => 'previewURLName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.date', $clientID), 'name' => 'date', 'trName' => 'dateName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.base', $clientID), 'name' => 'base', 'trName' => 'baseName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.total', $clientID), 'name' => 'total', 'trName' => 'totalName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.vat', $clientID), 'name' => 'vat', 'trName' => 'vatName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.ret', $clientID), 'name' => 'ret', 'trName' => 'retName');

$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.conceptID', $clientID), 'name' => 'conceptsItemTypeID', 'trName' => 'conceptsItemTypeName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.conceptFilterCriteria', $clientID), 'name' => 'conceptsFilterCriteriaID', 'trName' => 'conceptsFilterCriteriaName');

$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concept.%discount', $clientID), 'name' => 'concepts%discountPID', 'trName' => 'concepts%discountName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.vat', $clientID), 'name' => 'conceptsVATpID', 'trName' => 'conceptVATName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.ret', $clientID), 'name' => 'conceptsRETpID', 'trName' => 'conceptRETName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.base', $clientID), 'name' => 'conceptsBasePID', 'trName' => 'conceptBaseName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.description', $clientID), 'name' => 'conceptsDescriptionPID', 'trName' => 'conceptDescriptionName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.project', $clientID), 'name' => 'conceptsProjectPID', 'trName' => 'conceptProjectName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.stock', $clientID), 'name' => 'conceptsStockPID', 'trName' => 'conceptStockName');
$returnProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('financial.documents.concepts.units', $clientID), 'name' => 'conceptsUnitsPID', 'trName' => 'conceptUnitsName');


$documentEquiv = getClientPropertyIDRelatedWithByName('financial.documents.equiv', $clientID);
if ($documentEquiv != -1) {
    $returnProperties[] = array('ID' => $documentEquiv, 'name' => 'equiv', 'trName' => 'equivName');
}

$conceptsEquiv = getClientPropertyIDRelatedWithByName('financial.documents.concepts.equiv', $clientID);
if ($conceptsEquiv != -1) {
    $returnProperties[] = array('ID' => $conceptsEquiv, 'name' => 'conceptsEquivPID', 'trName' => 'conceptEquivName');
}

// get financial documents
$fds = getFilteredItemsIDs($itemTypeID, $clientID, array(), $returnProperties, '', true);

// get financial documents item types
$clientInvoicesItemType   = getClientItemTypeIDRelatedWithByName('invoice.client', $clientID);
$payrollItemType          = getClientItemTypeIDRelatedWithByName('payroll', $clientID);
$bankOperationItemType    = getClientItemTypeIDRelatedWithByName('operations', $clientID);
$ticketItemType           = getClientItemTypeIDRelatedWithByName('ticket', $clientID);

for ($i = 0; $i < count($fds); $i++) {

    switch ($fds[$i]['itemTypeID']) {

        case $clientInvoicesItemType:
            $fds[$i]['clientInvoice'] = '';
            break;

        case $payrollItemType:
            $fds[$i]['payroll'] = '';
            break;

        case $bankOperationItemType:
            $fds[$i]['operation'] = '';
            break;

        case $ticketItemType:
            $fds[$i]['ticket'] = '';
            break;
    }
}

// And write XML Response back to the application
RSreturnArrayQueryResults($fds);
