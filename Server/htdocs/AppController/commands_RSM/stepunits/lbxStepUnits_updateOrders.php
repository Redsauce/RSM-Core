<?php
//***************************************************
//Description:
//	 Update the parameters order
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentStepID = $GLOBALS['RS_POST']['parentStepID'];
$newOrder = explode(',', $GLOBALS['RS_POST']['newOrder']);

// get item types
$itemTypeStepID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
$orderUnitsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['orderUnits'], $clientID);

// get properties
$stepAssociatedCheckIDsPropID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);

$orderUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsUnitID'], $clientID);
$orderUnitsStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsStepID'], $clientID);
$orderUnitsOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsOrder'], $clientID);

// get order property type
$orderUnitsOrderPropertyType = getPropertyType($orderUnitsOrderPropertyID, $clientID);

// get the checked values for the step
$checkedArrayValues = explode(',', getItemPropertyValue($parentStepID, $stepAssociatedCheckIDsPropID, $clientID));

// get the parameters orders for the step
$filters = array();
$filters[] = array('ID' => $orderUnitsStepPropertyID, 'value' => $parentStepID);

$returnProperties = array();
$returnProperties[] = array('ID' => $orderUnitsPropertyID, 'name' => 'paramID');

$result = IQ_getFilteredItemsIDs($orderUnitsItemTypeID, $clientID, $filters, $returnProperties);

$orders = array();
while ($row = $result->fetch_assoc()) $orders[$row['paramID']]['ID'] = $row['ID'];

// calculate new orders
$ord = 1;
for ($k = count($newOrder) - 1; $k >= 0; $k--) {

    // get param ID
    $paramID = $newOrder[$k];

    if (in_array($paramID, $checkedArrayValues))
        if (isset($orders[$paramID])) {
            // assign a new order to the parameter
            $orders[$paramID]['newOrd'] = $ord++;
        } else {
            // an error was occurred: the parameter seems to be a checked parameter but does not have an order item associated
            $results['result'] = 'NOK';
            RSReturnArrayResults($results);
            exit ;
        }
    
}

// update data
foreach ($orders as $order)
    if (isset($order['newOrd']))
        setPropertyValueByID($orderUnitsOrderPropertyID, $orderUnitsItemTypeID, $order['ID'], $clientID, $order['newOrd'], $orderUnitsOrderPropertyType, $RSuserID);
    
$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
?>