<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentTestCaseID = $GLOBALS['RS_POST']['parentTestCaseID'];


// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$relatedStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRelatedID'], $clientID);
$parentTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
$orderPropertyID= getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);
$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsDescription'], $clientID);
$typePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsType'], $clientID);

// build the filter
$filters = array();
$filters[] = array('ID' => $parentTestCasePropertyID, 'value' => $parentTestCaseID);
$filters[] = array('ID' => $relatedStepPropertyID, 'value' => '0');

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
$returnProperties[] = array('ID' => $parentTestCasePropertyID, 'name' => 'parentTestCaseID');
$returnProperties[] = array('ID' => $descriptionPropertyID, 'name' => 'description');
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');
$returnProperties[] = array('ID' => $typePropertyID, 'name' => 'type');

// get the steps
$orderedSteps = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties, 'order');

// Return results
RSReturnQueryResults($orderedSteps);
?>