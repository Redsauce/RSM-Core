<?php
//***************************************************
//Description:
//	 Update a step checked value
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$stepUnitID = $GLOBALS['RS_POST']['stepUnitID'];
$parentStepID = $GLOBALS['RS_POST']['parentStepID'];
$stepChecked = $GLOBALS['RS_POST']['isChecked'];

// get item types
$itemTypeStepID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
$orderUnitsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['orderUnits'], $clientID);

// get properties
$stepAssociatedCheckIDsPropID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);

$orderUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsUnitID'], $clientID);
$orderUnitsStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsStepID'], $clientID);
$orderUnitsOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsOrder'], $clientID);

// get checked values for the step
$checkedArrayValues = explode(',', getItemPropertyValue($parentStepID, $stepAssociatedCheckIDsPropID, $clientID));

if ($stepChecked == '1') {
    if (!in_array($stepUnitID, $checkedArrayValues)) {
        // add value
        $checkedArrayValues[] = $stepUnitID;
    }
} else {
    if (in_array($stepUnitID, $checkedArrayValues)) {
        // remove values
        unset($checkedArrayValues[array_search($stepUnitID, $checkedArrayValues)]);
    }
}

// remove the possibles duplicates and re-arrange keys
$checkedArrayValues = array_merge(array_unique($checkedArrayValues));

// finally join the elements
$newValue = trim(implode(',', $checkedArrayValues), ',');

// set property value
setPropertyValueByID($stepAssociatedCheckIDsPropID, $itemTypeStepID, $parentStepID, $clientID, $newValue, '', $RSuserID);

if ($stepChecked == '1') {
    // create an order for the step unit
    $filters = array();
    $filters[] = array('ID' => $orderUnitsStepPropertyID, 'value' => $parentStepID);

    $returnProperties = array();
    $returnProperties[] = array('ID' => $orderUnitsOrderPropertyID, 'name' => 'order');

    // get the maximum order for the step
    $orders = getFilteredItemsIDs($orderUnitsItemTypeID, $clientID, $filters, $returnProperties, 'order', false, 1);

    if (count($orders) > 0) {
        $maxOrder = $orders[0]['order'] + 1;
    } else {
        $maxOrder = 1;
    }

    // create an order item for the parameter
    $values = array();
    $values[] = array('ID' => $orderUnitsPropertyID, 'value' => $stepUnitID);
    $values[] = array('ID' => $orderUnitsStepPropertyID, 'value' => $parentStepID);
    $values[] = array('ID' => $orderUnitsOrderPropertyID, 'value' => $maxOrder);

    $unitOrder = createItem($clientID, $values);

} else {
    // delete the order for the step unit
    $filters = array();
    $filters[] = array('ID' => $orderUnitsStepPropertyID, 'value' => $parentStepID);
    $filters[] = array('ID' => $orderUnitsPropertyID, 'value' => $stepUnitID);

    $order = getFilteredItemsIDs($orderUnitsItemTypeID, $clientID, $filters, array());

    if (count($order) > 0) {
        deleteItem($orderUnitsItemTypeID, $order[0]['ID'], $clientID);
    }
}

$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
?>