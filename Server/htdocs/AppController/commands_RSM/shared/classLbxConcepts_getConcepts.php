<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the item type and the properties
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['concepts'], $clientID);

$stockItemPropertyID     = getClientPropertyIDRelatedWithByName($definitions['conceptStockItemID'], $clientID);
$namePropertyID          = getClientPropertyIDRelatedWithByName($definitions['conceptName'], $clientID);
$projectPropertyID       = getClientPropertyIDRelatedWithByName($definitions['conceptProjectID'], $clientID);
$unitsPropertyID         = getClientPropertyIDRelatedWithByName($definitions['conceptUnits'], $clientID);
$IVAPropertyID           = getClientPropertyIDRelatedWithByName($definitions['conceptIVA'], $clientID);
$pricePropertyID         = getClientPropertyIDRelatedWithByName($definitions['conceptPrice'], $clientID);
$deductionPropertyID     = getClientPropertyIDRelatedWithByName($definitions['conceptDeduction'], $clientID);
$operationPropertyID     = getClientPropertyIDRelatedWithByName($definitions['conceptOperationID'], $clientID);
$pendingStockPropertyID  = getClientPropertyIDRelatedWithByName($definitions['conceptPendingStockID'], $clientID);

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
