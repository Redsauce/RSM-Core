<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentTestCaseID = $GLOBALS['RS_POST']['parentTestCaseID'];
$stepMainValue = base64_decode($GLOBALS['RS_POST']['mainValue']);
$stepDescription = base64_decode($GLOBALS['RS_POST']['description']);


// get item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
	
// get properties
$parentTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
$orderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);


//build the filter
$filters = array();
$filters[] = array('ID' => $parentTestCasePropertyID, 'value' => $parentTestCaseID);

//Next, get the orders for this parent category 
$returnProperties = array();
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');

$result = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties, 'order', false);

if (count($result) > 0) {
	$maxOrder = $result[count($result)-1]['order']+1;
} else {
	$maxOrder = 1;
}


// create new step
$values = array();
$values[]=array('ID' => $parentTestCasePropertyID, 'value' => $parentTestCaseID);
$values[]=array('ID' => $orderPropertyID, 'value' => $maxOrder);
$values[]=array('ID' => getMainPropertyID($itemTypeID, $clientID), 'value' => $stepMainValue);
$values[]=array('ID' => getClientPropertyID_RelatedWith_byName($definitions['stepsDescription'], $clientID), 'value' => $stepDescription);


$stepID = createItem($clientID, $values);


$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);	
?>