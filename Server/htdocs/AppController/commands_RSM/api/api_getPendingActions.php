<?php
header('Access-Control-Allow-Origin: *');
// ****************************************************************************************
//Description:
//    Retrieves specified number of pending actions (events) and assign them to passed node
//
//   RStoken      : A valid token for pending actions retrieving and updating
//   numActions  : Number of actions to retrieve
//   nodeID      : ID of the node serverApp the actions will be assigned to
// ****************************************************************************************

// Capture entry memory before the database/security bootstrap. Keep only scalar
// counts and memory figures; emit only under the existing debug/50 MiB policy.
$pendingActionsMemoryPhases = array();
$pendingActionsMemoryContext = array();
$recordPendingActionsMemory = function ($phase, $rowCount = 0) use (&$pendingActionsMemoryPhases) {
    $pendingActionsMemoryPhases[] = array('phase' => $phase, 'rows' => intval($rowCount),
        'usedMiB' => round(memory_get_usage() / 1048576, 2),
        'allocatedMiB' => round(memory_get_usage(true) / 1048576, 2),
        'peakUsedMiB' => round(memory_get_peak_usage() / 1048576, 2),
        'peakAllocatedMiB' => round(memory_get_peak_usage(true) / 1048576, 2));
};
$recordPendingActionsMemory('entry');
register_shutdown_function(function () use (&$pendingActionsMemoryPhases, &$pendingActionsMemoryContext, $recordPendingActionsMemory) {
    if (empty($GLOBALS['RSallowDebug'])
        || max(memory_get_peak_usage(), memory_get_peak_usage(true)) <= 50 * 1048576) {
        return;
    }
    $recordPendingActionsMemory('response_finished');
    error_log('RSM pending actions memory phases: ' . json_encode(array(
        'context' => $pendingActionsMemoryContext, 'phases' => $pendingActionsMemoryPhases)));
});

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
$recordPendingActionsMemory('bootstrap_finished');
// Definitions
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken         = $GLOBALS[$cstRS_POST][$cstRStoken] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["nodeID"         ]) ? $nodeID          = $GLOBALS[$cstRS_POST]['nodeID'         ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["numActions"     ]) ? $numActions      = $GLOBALS[$cstRS_POST]['numActions'     ] : $numActions = "5";

$clientID = RSclientFromToken($RStoken);
$pendingActionsMemoryContext['clientID'] = intval($clientID);
if (is_scalar($numActions) && ctype_digit((string)$numActions)) {
    $pendingActionsMemoryContext['requestedActions'] = intval($numActions);
}
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
$eventFilterProperties = RSappendTokenCustomerScopeFilter($RStoken, $clientID, $eventItemTypeID, $eventFilterProperties);
if ($eventFilterProperties === false) {
    $results['result'] = 'NOK';
    $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO EVENTS';
    RSReturnArrayResults($results, false);
}
$eventResults = getFilteredItemsIDs($eventItemTypeID, $clientID, $eventFilterProperties, $eventReturnProperties, '', true, '', '', 'OR');
$recordPendingActionsMemory('allowed_events_loaded', count($eventResults));

// Join list of resulting event IDs
$allowedEventIds = array();

foreach ($eventResults as $row) {
    $allowedEventIds[] = $row['ID'];
}
$recordPendingActionsMemory('allowed_event_ids_built', count($allowedEventIds));

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
$filterProperties = RSappendTokenCustomerScopeFilter($RStoken, $clientID, $itemTypeID, $filterProperties);
if ($filterProperties === false) {
    $results['result'] = 'NOK';
    $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO SCHEDULED EVENTS';
    RSReturnArrayResults($results, false);
}
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy, true, $numActions, '', 'AND', 0, false, "", true);
$recordPendingActionsMemory('pending_actions_loaded', count($results));

//Update retrieved actions node
foreach ($results as $row) {
    setPropertyValueByID("scheduledEvents.node", $itemTypeID, $row['ID'], $clientID, $nodeID);
}
$recordPendingActionsMemory('node_assignment_finished', count($results));

// And write XML Response back to the application without compression// Return results
$recordPendingActionsMemory('before_response', count($results));
RSReturnArrayQueryResults($results, false);
