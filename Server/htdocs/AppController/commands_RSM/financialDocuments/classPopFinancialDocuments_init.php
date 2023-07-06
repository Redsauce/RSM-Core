<?php
// Database connection startup and required files
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);

// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName('financial.documents', $clientID);

// build return properties array
$returnProperties = array();

$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.itemTypeID'    , $clientID), 'name' => 'itemTypeID'              , 'trName' => 'itemTypeName'              );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.subItemTypeID' , $clientID), 'name' => 'subItemTypeID'           , 'trName' => 'subItemTypeName'           );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.filterCriteria', $clientID), 'name' => 'filterCriteriaID'        , 'trName' => 'filterCriteriaName'        );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.previewURL'    , $clientID), 'name' => 'previewURL'              , 'trName' => 'previewURLName'            );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.date'          , $clientID), 'name' => 'date'                    , 'trName' => 'dateName'                  );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.base'          , $clientID), 'name' => 'base'                    , 'trName' => 'baseName'                  );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.total'         , $clientID), 'name' => 'total'                   , 'trName' => 'totalName'                 );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.vat'           , $clientID), 'name' => 'vat'                     , 'trName' => 'vatName'                   );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.ret'           , $clientID), 'name' => 'ret'                     , 'trName' => 'retName'                   );

$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.conceptID'            , $clientID), 'name' => 'conceptsItemTypeID'    , 'trName' => 'conceptsItemTypeName');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.conceptFilterCriteria', $clientID), 'name' => 'conceptsFilterCriteriaID', 'trName' => 'conceptsFilterCriteriaName');

$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concept.%discount'   , $clientID), 'name' => 'concepts%discountPID'  , 'trName' => 'concepts%discountName' );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.vat'        , $clientID), 'name' => 'conceptsVATpID'        , 'trName' => 'conceptVATName'        );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.ret'        , $clientID), 'name' => 'conceptsRETpID'        , 'trName' => 'conceptRETName'        );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.base'       , $clientID), 'name' => 'conceptsBasePID'       , 'trName' => 'conceptBaseName'       );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.description', $clientID), 'name' => 'conceptsDescriptionPID', 'trName' => 'conceptDescriptionName');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.project'    , $clientID), 'name' => 'conceptsProjectPID'    , 'trName' => 'conceptProjectName'    );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.stock'      , $clientID), 'name' => 'conceptsStockPID'      , 'trName' => 'conceptStockName'      );
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName('financial.documents.concepts.units'      , $clientID), 'name' => 'conceptsUnitsPID'      , 'trName' => 'conceptUnitsName'      );


$documentEquiv = getClientPropertyID_RelatedWith_byName('financial.documents.equiv', $clientID);
if ($documentEquiv != -1) {
    $returnProperties[] = array('ID' => $documentEquiv, 'name' => 'equiv', 'trName' => 'equivName');
}

$conceptsEquiv = getClientPropertyID_RelatedWith_byName('financial.documents.concepts.equiv', $clientID);
if ($conceptsEquiv != -1) {
    $returnProperties[] = array('ID' => $conceptsEquiv, 'name' => 'conceptsEquivPID', 'trName' => 'conceptEquivName');
}

// get financial documents
$fds = getFilteredItemsIDs($itemTypeID, $clientID, array(), $returnProperties, '', true);

// get financial documents item types
$clientInvoicesItemType   = getClientItemTypeID_RelatedWith_byName('invoice.client', $clientID);
$payrollItemType          = getClientItemTypeID_RelatedWith_byName('payroll'       , $clientID);
$bankOperationItemType    = getClientItemTypeID_RelatedWith_byName('operations'    , $clientID);
$ticketItemType           = getClientItemTypeID_RelatedWith_byName('ticket'        , $clientID);

for ($i = 0; $i < count($fds); $i++) {

    switch ($fds[$i]['itemTypeID']) {

        case $clientInvoicesItemType :
            $fds[$i]['clientInvoice'] = '';
            break;

        case $payrollItemType :
            $fds[$i]['payroll'] = '';
            break;

        case $bankOperationItemType :
            $fds[$i]['operation'] = '';
            break;

        case $ticketItemType :
            $fds[$i]['ticket'] = '';
            break;
    }
}

// And write XML Response back to the application
RSReturnArrayQueryResults($fds);
