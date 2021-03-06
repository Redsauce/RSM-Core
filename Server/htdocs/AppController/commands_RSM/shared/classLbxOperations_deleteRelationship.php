<?php
//***************************************************
//Description:
//	Delete a relationship between a two operations
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operation_1 = $GLOBALS['RS_POST']['operation_1'];
// a statement
$operation_2 = $GLOBALS['RS_POST']['operation_2'];
// an invoice
$resetPayDate = $GLOBALS['RS_POST']['resetPayDate'];

// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get properties
$propertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);
$statusPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationStatus'], $clientID);

// remove the operations from the lists
removeIdentifier($operation_2, $itemTypeID, $operation_1, $propertyID, $clientID, $RSuserID);
removeIdentifier($operation_1, $itemTypeID, $operation_2, $propertyID, $clientID, $RSuserID);

// get the closed status
$closedStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusClosed'), $clientID), $clientID);

if (getItemPropertyValue($operation_1, $statusPropertyID, $clientID) == $closedStatus) {
    // get the open status
    $openStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationStatusOpen'), $clientID), $clientID);

    // open operation_1
    setPropertyValueByID($statusPropertyID, $itemTypeID, $operation_1, $clientID, $openStatus, '', $RSuserID);

    // return close: yes
    $results['newStatus'] = $openStatus;
}

$results['resetPayDate'] = $resetPayDate;

if ($resetPayDate == '1') {
    // reset pay date to default value
    $payDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationPayDate'], $clientID);

    setPropertyValueByID($payDatePropertyID, $itemTypeID, $operation_2, $clientID, getClientPropertyDefaultValue($payDatePropertyID, $clientID), '', $RSuserID);

    $results['payDate'] = getPropertyValue($definitions['operationPayDate'], $itemTypeID, $operation_2, $clientID);
}

// And write XML response back to the application
RSReturnArrayResults($results);
?>