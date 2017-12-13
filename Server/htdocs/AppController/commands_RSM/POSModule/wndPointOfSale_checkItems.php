<?php
//***************************************************
//Description:
//	updates the item properties
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the pendingStock item type
$pendingStockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['pendingStock'], $clientID);
// get the pendingStock properties
$pendingStockItemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockItemID'], $clientID);
$pendingStockOperationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockOperationID'], $clientID);

//remove pendingStock
$filterProperties = array();
$filterProperties[] = array('ID' => $pendingStockOperationPropertyID, 'value' => $operationID);

$returnProperties = array();

$result = IQ_getFilteredItemsIDs($pendingStockItemTypeID, $clientID, $filterProperties, $returnProperties);
//TO DO COMPARE PENDING RECORD WITH VALUE IN CONCEPT

while($row=$result->fetch_assoc()){	
	deleteItem($pendingStockItemTypeID, $row['ID'], $clientID);
}

$results['result']="OK";

// And write XML Response back to the application
RSReturnArrayResults($results);
?>