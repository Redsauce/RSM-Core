<?php
// ****************************************************************************************
//Description:
//    Retrieves specified number of pending actions (events) and assign them to passed node
//
//   RStoken      : A valid token for pending actions retrieving and updating
//   numActions  : Number of actions to retrieve
//   nodeID      : ID of the node serverApp the actions will be assigned to
// ****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "./api_headers.php";

// Definitions
isset($GLOBALS["RS_POST"]["RStoken"        ]) ? $RStoken         = $GLOBALS["RS_POST"]["RStoken"        ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["nodeID"         ]) ? $nodeID          = $GLOBALS['RS_POST']['nodeID'         ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["numActions"     ]) ? $numActions      = $GLOBALS['RS_POST']['numActions'     ] : $numActions = "5";

$clientID = RSclientFromToken($RStoken);
$itemTypeID = parseITID("scheduledEvents", $clientID);

$eventItemTypeID = parseITID("event", $clientID);
$eventServerPropertyID = parsePID("event.serverAppID", $clientID);

// Check if user has permissions to read properties of the item
$propertyIDs = array("scheduledEvents.event", "scheduledEvents.parameters", "scheduledEvents.priority");
if (!RShasTokenPermissions($RStoken, $propertyIDs, "READ")) {
    $results['result'] = 'NOK';
    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO READ THESE ITEMS';
    RSReturnArrayResults($results, false);
}
if (!RShasTokenPermissions($RStoken, array("scheduledEvents.node"), "WRITE")) {
    $results['result'] = 'NOK';
    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO UPDATE THESE ITEMS';
    RSReturnArrayResults($results, false);
}

// First we need to check the events allowed for current node (not assigned to a specific node or assigned to current one)
// Construct filterProperties array
$eventFilterProperties  = array(
    array('ID' => $eventServerPropertyID, 'value' => "0", 'mode' => "="),
    array('ID' => $eventServerPropertyID, 'value' => $nodeID, 'mode' => "=")
);

// Construct returnProperties array
$eventReturnProperties = array();

// Filter results
$eventResults = array();
$eventResults = getFilteredItemsIDs($eventItemTypeID, $clientID, $eventFilterProperties, $eventReturnProperties, '', true, '', '', 'OR');

// Join list of resulting event IDs
$allowedEventIds = array();

foreach ($eventResults as $row) {
    $allowedEventIds[] = $row['ID'];
}

// Now we get the required number of pending scheduled events based on previous conditions
// Construct filterProperties array
$filterProperties  = array(
    array('ID' => parsePID("scheduledEvents.node", $clientID), 'value' => "0", 'mode' => "="),
    array('ID' => parsePID("scheduledEvents.event", $clientID), 'value' => join(',', $allowedEventIds), 'mode' => "<-IN")
);

// Construct returnProperties array
$returnProperties = array();
foreach ($propertyIDs as $property) {
    $returnProperties[] = array('ID' => ParsePID($property, $clientID), 'name' => $property, 'trName' => $property . 'trs');
}

//order by priority
$orderBy = "scheduledEvents.priority";

// Filter results
$results = array();
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy, true, $numActions, '', 'AND', 0, false, "", true);

//Update retrieved actions node
foreach ($results as $row) {
    setPropertyValueByID("scheduledEvents.node", $itemTypeID, $row['ID'], $clientID, $nodeID);
}

// And write XML Response back to the application without compression// Return results
RSReturnArrayQueryResults($results, false);
