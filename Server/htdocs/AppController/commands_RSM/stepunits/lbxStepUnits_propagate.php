<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentStepID = $GLOBALS['RS_POST']['parentStepID'];
$stepUnitID = $GLOBALS['RS_POST']['stepUnitID'];
$roundIDs = $GLOBALS['RS_POST']['roundIDs'];
$stepChecked = $GLOBALS['RS_POST']['isChecked'];


// get steps item type
$itemTypeStepID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
$itemTypeStepUnitID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

// get properties IDs
$relatedStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRelatedID'], $clientID);
$roundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRoundID'], $clientID);
$stepAssociatedCheckIDsPropID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);
$stepAssociatedCheckIDsPropType = getPropertyType($stepAssociatedCheckIDsPropID, $clientID);

// prepare filter
$filters = array();
$filters[] = array('ID' => $relatedStepPropertyID, 'value' => $parentStepID);
$filters[] = array('ID' => $roundPropertyID, 'value' => $roundIDs, 'mode' => '<-IN');

// prepare return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $stepAssociatedCheckIDsPropID, 'name' => 'checkedStepUnits');

// get step instances
$steps = getFilteredItemsIDs($itemTypeStepID, $clientID, $filters, $returnProperties);

foreach ($steps as $step) {
	
	// get checked values for the step instance
	$checkedArrayValues = explode(',', $step['checkedStepUnits']);

	if ($stepChecked == '1') {
		if (!in_array($stepUnitID, $checkedArrayValues)) $checkedArrayValues[] = $stepUnitID;  // add value
	} else {
		if (in_array($stepUnitID, $checkedArrayValues)) unset($checkedArrayValues[array_search($stepUnitID, $checkedArrayValues)]);  // remove value
	}

	// remove duplicates and re-arrange keys
	$checkedArrayValues = array_merge(array_unique($checkedArrayValues));

	// finally join the elements
	$newValue = trim(implode(',', $checkedArrayValues), ',');

	// update property
	setPropertyValueByID($stepAssociatedCheckIDsPropID, $itemTypeStepID, $step['ID'], $clientID, $newValue, $stepAssociatedCheckIDsPropType, $RSuserID);
}


$results['result'] = 'OK'; 

// Return results
RSReturnArrayResults($results);
?>