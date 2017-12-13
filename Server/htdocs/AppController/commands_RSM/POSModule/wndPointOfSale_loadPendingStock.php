<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the pendingStock item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['pendingStock'], $clientID);

// get the properties
$operationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockOperationID'], $clientID);
$itemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockItemID'], $clientID);
$amountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockAmount'], $clientID);

// build the filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $operationPropertyID, 'value' => $operationID);

// build the return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $itemPropertyID, 'name' => 'itemID');
$returnProperties[] = array('ID' => $amountPropertyID, 'name' => 'amount');

// get the pendingStock
$results = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

// Return results
RSReturnArrayQueryResults($results);
?>