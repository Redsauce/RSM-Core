<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

$stockItemPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['conceptStockItemID'], $clientID);
$namePropertyID          = getClientPropertyID_RelatedWith_byName($definitions['conceptName'], $clientID);
$projectPropertyID       = getClientPropertyID_RelatedWith_byName($definitions['conceptProjectID'], $clientID);
$unitsPropertyID         = getClientPropertyID_RelatedWith_byName($definitions['conceptUnits'], $clientID);
$IVAPropertyID           = getClientPropertyID_RelatedWith_byName($definitions['conceptIVA'], $clientID);
$pricePropertyID         = getClientPropertyID_RelatedWith_byName($definitions['conceptPrice'], $clientID);
$deductionPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['conceptDeduction'], $clientID);
$operationPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID);
$pendingStockPropertyID  = getClientPropertyID_RelatedWith_byName($definitions['conceptPendingStockID'], $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $operationPropertyID, 'value' => $operationID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $stockItemPropertyID, 'name' => 'stockItemID', 'trName' => 'stockItem');
$returnProperties[] = array('ID' => $namePropertyID, 'name' => 'name');
$returnProperties[] = array('ID' => $projectPropertyID, 'name' => 'projectID', 'trName' => 'project');
$returnProperties[] = array('ID' => $unitsPropertyID, 'name' => 'units');
$returnProperties[] = array('ID' => $IVAPropertyID, 'name' => 'VAT');
$returnProperties[] = array('ID' => $pricePropertyID, 'name' => 'price');
$returnProperties[] = array('ID' => $deductionPropertyID, 'name' => 'deduction');

if ($pendingStockPropertyID != 0) {
    $returnProperties[] = array('ID' => $pendingStockPropertyID, 'name' => 'pendingStockID', 'trName' => 'pendingStock');
}

// get invoice concepts
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);

// Return results
RSreturnArrayQueryResults($results);
