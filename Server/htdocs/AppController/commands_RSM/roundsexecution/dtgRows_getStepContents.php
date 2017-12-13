<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "paramsAndResultsUtilities.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$roundID = $GLOBALS['RS_POST']['roundID'];
$parentTestCaseID = $GLOBALS['RS_POST']['parentTestCaseID'];
$parentStudyID = $GLOBALS['RS_POST']['parentStudyID'];
$parentSubjectID = $GLOBALS['RS_POST']['parentSubjectID'];

//Get itemtypes
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
$relationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

//Get properties
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$parentTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
$relatedStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRelatedID'], $clientID);
$roundSubjectTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRoundSubjectRelationID'], $clientID);
$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsDescription'], $clientID);
$orderPropertyID= getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);
$stepCheckedStepUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'],$clientID);
$relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'],$clientID);
$relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'],$clientID);

//First, we need the id for the relation used for subject-round-testcase
$returnProperties = array();

$filters = array();
$filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $roundID);
$filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $parentSubjectID);

$relations = getFilteredItemsIDs($relationsItemTypeID, $clientID, $filters, $returnProperties);

$finalResult = array();

//Next, check that has one $relation
if (count($relations)>0){
	
	// build return properties array
	$returnProperties = array();
	$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
	$returnProperties[] = array('ID' => $parentTestCasePropertyID, 'name' => 'parentTestCaseID');
	$returnProperties[] = array('ID' => $descriptionPropertyID, 'name' => 'description');
	$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');
	$returnProperties[] = array('ID' => $stepCheckedStepUnitsPropertyID, 'name' => 'checkedValues');
	$returnProperties[] = array('ID' => $relatedStepPropertyID, 'name' => 'stepRelated');
	
	//build the filter
	$filters = array();
	$filters[] = array('ID' => $parentTestCasePropertyID, 'value' => $parentTestCaseID);
	$filters[] = array('ID' => $roundSubjectTestCasePropertyID, 'value' => $relations[0]['ID']);
	$filters[] = array('ID' => $relatedStepPropertyID, 'value' => 0, 'mode' => '<>');
	
	// get steps
	$orderedSteps = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties, 'order');
	
	
	//Add the "isStep" parameter
	for ($i=0;$i<count($orderedSteps);$i++){
		
		//First, encode the name and the description
		$orderedSteps[$i]['mainValue'] = base64_encode($orderedSteps[$i]['mainValue'] );
		$orderedSteps[$i]['description'] = base64_encode($orderedSteps[$i]['description'] );
		$orderedSteps[$i]['isStep']='True';
		
		$finalResult[] = $orderedSteps[$i];
		
		//Next, save the values to an array and return it
		if (strlen($orderedSteps[$i]['checkedValues']) > 0) {
			
			// get checked values
			$markedStepsUnitsIDs = explode(',', $orderedSteps[$i]['checkedValues']);
			
			// get params
			$params = getParamsAndResultsForAStep($orderedSteps[$i], $parentStudyID, $parentSubjectID, $markedStepsUnitsIDs, $clientID);
			
			//And merge next the step
			foreach ($params as $param){
				//array_push($finalResult, $param);
				$finalResult[] = $param;
			}
		}
	}
}

// return results
RSReturnArrayQueryResults($finalResult);
?>