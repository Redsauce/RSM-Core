<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];


// get operation item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);



// get operation concepts...
$conceptsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID), 'value' => $operationID);

$concepts = IQ_getFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, array());

// ... and delete them
while ($row = $concepts->fetch_assoc()) {
	deleteItem($conceptsItemTypeID, $row['ID'], $clientID);
}




// ... get the operation's related operations...
$relatedOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);

$filterProperties = array();
$filterProperties[] = array('ID' => $relatedOperationsPropertyID, 'value' => $operationID, 'mode' => 'IN');

$relatedOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, array());

// ... and delete the relations
while ($row = $relatedOperations->fetch_assoc()) {
	removeIdentifier($operationID, $itemTypeID, $row['ID'], $relatedOperationsPropertyID, $clientID, $RSuserID);
}


// finally delete the operation
deleteItem($itemTypeID, $operationID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
?>