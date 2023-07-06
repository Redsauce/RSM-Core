<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];


// get operation item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);



// get operation concepts...
$conceptsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['concepts'], $clientID);

$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['conceptOperationID'], $clientID), 'value' => $operationID);

$concepts = iqGetFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, array());

// ... and delete them
while ($row = $concepts->fetch_assoc()) {
    deleteItem($conceptsItemTypeID, $row['ID'], $clientID);
}




// ... get the operation's related operations...
$relatedOperationsPropertyID = getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID);

$filterProperties = array();
$filterProperties[] = array('ID' => $relatedOperationsPropertyID, 'value' => $operationID, 'mode' => 'IN');

$relatedOperations = iqGetFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, array());

// ... and delete the relations
while ($row = $relatedOperations->fetch_assoc()) {
    removeIdentifier($operationID, $itemTypeID, $row['ID'], $relatedOperationsPropertyID, $clientID, $RSuserID);
}


// finally delete the operation
deleteItem($itemTypeID, $operationID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSreturnArrayResults($results);
