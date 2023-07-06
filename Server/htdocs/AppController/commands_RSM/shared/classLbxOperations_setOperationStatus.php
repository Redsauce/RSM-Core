<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$action = $GLOBALS['RS_POST']['action'];


// get the operations item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);

if ($action == 'open') {
    // retrieve the status
    $openStatus = getValue(getClientListValueIDRelatedWith(getAppListValueID('operationStatusOpen'), $clientID), $clientID);
    // set new status
    setItemPropertyValue($definitions['operationStatus'], $itemTypeID, $operationID, $clientID, $openStatus, $RSuserID);
} else {
    // retrieve the status
    $closedStatus = getValue(getClientListValueIDRelatedWith(getAppListValueID('operationStatusClosed'), $clientID), $clientID);
    // set new status
    setItemPropertyValue($definitions['operationStatus'], $itemTypeID, $operationID, $clientID, $closedStatus, $RSuserID);
}



$results['status'] = getPropertyValue($definitions['operationStatus'], $itemTypeID, $operationID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
