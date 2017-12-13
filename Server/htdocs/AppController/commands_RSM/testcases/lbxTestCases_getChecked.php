<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
isset($GLOBALS['RS_POST']['clientID'       ]) ? $clientID       =               $GLOBALS['RS_POST']['clientID'       ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['studyID'       ]) ? $studyID       =               $GLOBALS['RS_POST']['studyID'       ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['roundID'       ]) ? $roundID       =               $GLOBALS['RS_POST']['roundID'       ]  : $roundID = -1;
isset($GLOBALS['RS_POST']['subjectID'       ]) ? $subjectID       =               $GLOBALS['RS_POST']['subjectID'       ]  : $subjectID = -1;

// prepare results array
$results = array();

// get item types
$roundsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);
$groupsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);
$categoriesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$testcasesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
$relationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

//get Properties
$relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
$relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
$relationsTestCasesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
$relationsTestCategoriesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);

// 1a) get associated test cases for the round passed or for the subject passed or for the round and subject passed

$relations = array();
// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $relationsTestCasesPropertyID, 'name' => 'testCasesIDs');
$returnProperties[] = array('ID' => $relationsTestCategoriesPropertyID, 'name' => 'testCatIDs');

//build an empty filter
$filters = array();


if ($roundID>-1 && $subjectID>-1)
{
	//We have selected a round and subject
	//Get the associated testcases
	$filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $roundID);
	$filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $subjectID);



} elseif ($roundID>-1 && $subjectID==-1) {
	//We only have selected a round. Get all test cases for this round
	$filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $roundID);

} elseif ($roundID==-1 && $subjectID>-1) {

	//We only have selected a subject. Get all test cases for this subject
	$filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $subjectID);
} else {
	//Error. We need almost one of two ids.
	$results['result']='NOK';
	$results['explanation']='We have not received any id. RoundID: '.$roundID.', SubjectID: '.$subjectID;
	RSReturnArrayQueryResults($results);
}

$relations = getFilteredItemsIDs($relationsItemTypeID, $clientID, $filters, $returnProperties);

//Next, we need to merge all results, and remove duplicated entries
$testCasesChecked = mergeAndCleanResults($relations,'testCasesIDs',',');
$testCategoriesChecked = mergeAndCleanResults($relations,'testCatIDs',',');

//Next, clear the keys
$auxString = implode(',',$testCasesChecked);
$auxString2 = implode(',',$testCategoriesChecked);

//And rearm the array
$testCasesChecked = explode(',',$auxString);
$testCategoriesChecked=explode(',',$auxString2);


//If not checked test cases, return an empty array
if (count($testCasesChecked) == 0) {
	// return empty array
	RSReturnArrayQueryResults($results);
	exit;
}

// 2) get the groups
$groups = getFilteredItemsIDs(
	$groupsItemTypeID,
	$clientID,
	array(array('ID' => getClientPropertyID_RelatedWith_byName($definitions['groupsStudyID'], $clientID), 'value' => $studyID)),
	array()
);

// 3) get the root categories for each group
//$categoryNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryName'], $clientID);
$categoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$categoryGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$testCasesCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);

$resultArray=array();

foreach ($groups as $group) {

	//Get all testcategories ids for this group
	$returnProperties = array();
    $returnProperties[] = array('ID' => $categoryParentPropertyID, 'name' => 'parentCategoryID');

	//build an empty filter
	$filters = array();
	$filters[] = array('ID' => $categoryGroupPropertyID, 'value' => $group['ID']);
	$filters[] = array('ID' => $categoryParentPropertyID, 'value' => '0');

	$testCategories = getFilteredItemsIDs($categoriesItemTypeID, $clientID, $filters, $returnProperties);

	$tcMarked=0;
	$tcUnmarked=0;
	$tcUndefined = 0;
	$resultTreeChecks = array();

   foreach($testCategories as $tc){
			//Process child results
			$resultTreeChecks[] = processTestCategory ($tc, $testCasesChecked,$testCategoriesChecked);

	}

	for ($i=0;$i<count($resultTreeChecks);$i++){
		if ($resultTreeChecks[$i]=='UNDEFINED'){
				$tcUndefined++;
		} elseif ($resultTreeChecks[$i]=='NOMARK'){
				$tcUnmarked++;
		} elseif ($resultTreeChecks[$i]=='MARK'){
				$tcMarked ++;
		}
	}

	$group['Type']='GROUP';
	$group['CheckState']='NOMARK'; //by default

	if ($tcUndefined>0) {
		$group['CheckState']='UNDEFINED';
	} elseif ($tcMarked>0 && $tcUnmarked==0){
		$group['CheckState']='MARK';
	} elseif ($tcMarked==0 && $tcUnmarked>0){
		$group['CheckState']='NOMARK';
	} elseif ($tcMarked>0 && $tcUnmarked>0){
		$group['CheckState']='UNDEFINED';
	}

	//Add the group to the results
	$resultArray[] = $group;

}
//print ("Returned final result: \n");
//print_r($resultArray);
//exit;

// Return results
RSReturnArrayQueryResults($resultArray);

//Merge all included results in a property and clean it
function mergeAndCleanResults($arrays, $propertyName,$parsedGlue){

	$resultArray = array();
	foreach ($arrays as $singleArray){
		$auxArray = explode($parsedGlue,$singleArray[$propertyName]);
		$cleanedArray = array_filter($auxArray);
		$resultArray = array_merge($resultArray,$cleanedArray);
	}

	$finalArray = array();
	//Next, delete duplicated results
	for ($i=0;$i<(count($resultArray));$i++){
		$valueExist = False;
		for($j=0;$j<(count($finalArray));$j++){
			if ($resultArray[$i]==$finalArray[$j]){
				$valueExist=True;
				break;
			}
		}
		if (!$valueExist){
			//Append the value
			$finalArray[]=$resultArray[$i];
		}
	}

	return $finalArray;
}

function processTestCategory ($testCategoryParent, &$relationsCheckedTestCases, &$relationsCheckedTestCategories)
{
	global $testCasesCategoryPropertyID, $clientID,$categoryParentPropertyID,$categoriesItemTypeID, $testcasesItemTypeID,$resultArray,$categoryGroupPropertyID;
	//Mark the parent testCategory
	$testCategoryParent['Type']='TESTCAT';

	$isMarked = False;
	//First, check if the test category is in the relation
	if (in_array($testCategoryParent['ID'],$relationsCheckedTestCategories,true)){
		$isMarked = True;
	} else {
		$isMarked = False;
	}

	if (!$isMarked){
		//Return the state directly because this test category is not in the list
		$testCategoryParent['CheckState']='NOMARK';
		//return $testCategoryParent['CheckState'];
	}



	//Get all testcases inside this category
	$returnProperties = array();

	//build an empty filter
	$filters = array();
	$filters[] = array('ID' => $testCasesCategoryPropertyID, 'value' => $testCategoryParent['ID']);

	$testCases = getFilteredItemsIDs($testcasesItemTypeID, $clientID, $filters, $returnProperties);

	$markedTestCases=0;
	$unmarkedTestCases=0;

	//Check state of every testCase
	for ($i=0;$i<count($testCases);$i++){
			//$found = false;
			$testCases[$i]['Type']='TESTCASE';

			if (in_array($testCases[$i]['ID'], $relationsCheckedTestCases,true)) {
				//$found=true;
				$markedTestCases ++;
				//Mark the testCase
				$testCases[$i]['CheckState']='MARK';
			} else {
				$unmarkedTestCases ++;
				//UnMark the testCase
				$testCases[$i]['CheckState']='NOMARK';
			}
			//Add the testcase to the results
			$resultArray[] = $testCases[$i];
	}



	//process the childs, if necessary
	$returnProperties = array();
    $returnProperties[] = array('ID' => $categoryParentPropertyID, 'name' => 'parentCategoryID');

	//build an empty filter
	$filters = array();
	$filters[] = array('ID' => $categoryParentPropertyID, 'value' => $testCategoryParent['ID']);

	$childsTestCategories = getFilteredItemsIDs($categoriesItemTypeID, $clientID, $filters, $returnProperties);

	//Check the childs status
	$markedChilds = 0;
	$unmarkedChilds = 0;
	$undefinedChilds = 0;

	$cResults = array();

	foreach ($childsTestCategories as $child)
	{
		//Process the child and subchilds
		$cResults[] = processTestCategory($child, $relationsCheckedTestCases, $relationsCheckedTestCategories);
	}

	for($i=0;$i<count($cResults);$i++){
		if ($cResults[$i]=='MARK'){
			$markedChilds ++;
		} elseif ($cResults[$i]=='NOMARK') {
			$unmarkedChilds ++;
		} elseif ($cResults[$i]=='UNDEFINED'){
			$undefinedChilds ++;
		}
	}

	//Mark the parent using their childs state
	if ($undefinedChilds>0)
	{
		//Change the status of the testCase
		$testCategoryParent['CheckState']='UNDEFINED';
	} elseif ($markedChilds>0 && $unmarkedChilds>0) {
		//Change the status of the testCase
		$testCategoryParent['CheckState']='UNDEFINED';
	} elseif ($markedChilds>0 && $unmarkedChilds==0) {
		if ($markedTestCases>0 && $unmarkedTestCases==0)
		{
			//All testcases and child categories are marked
			$testCategoryParent['CheckState']='MARK';
		} elseif ($markedTestCases==0 && $unmarkedTestCases>0) {
			//All testcases unmarked and all child categories marked
			$testCategoryParent['CheckState']='UNDEFINED';
		} elseif ($markedTestCases>0 && $unmarkedTestCases>0) {
			//Some testCases are marked and others unmarked
			$testCategoryParent['CheckState']='UNDEFINED';
		} elseif ($markedTestCases==0 && $unmarkedTestCases==0){
			//No testcases detected
			//All child categories marked
			$testCategoryParent['CheckState']='MARK';
		}

	} elseif ($markedChilds==0 && $unmarkedChilds>0) {

		if ($markedTestCases>0 && $unmarkedTestCases==0)
		{
			//All testcases marked and all child test categories unmarked
			$testCategoryParent['CheckState']='UNDEFINED';
		} elseif ($markedTestCases==0 && $unmarkedTestCases>0) {
			//All testcases unmarked and all child categories unmarked
			$testCategoryParent['CheckState']='NOMARK';
		} elseif ($markedTestCases>0 && $unmarkedTestCases>0) {

			//Some testCases are marked and others unmarked
			$testCategoryParent['CheckState']='UNDEFINED';
		} elseif ($markedTestCases==0 && $unmarkedTestCases==0){
			//No testcases detected
			//All child categories unmarked
			$testCategoryParent['CheckState']='NOMARK';
		}
	} elseif ($markedChilds==0 && $unmarkedChilds==0) {

		if ($markedTestCases>0 && $unmarkedTestCases==0)
		{
			//All testcases marked
			$testCategoryParent['CheckState']='MARK';
		} elseif ($markedTestCases==0 && $unmarkedTestCases>0) {
			//All testcases unmarked
			$testCategoryParent['CheckState']='NOMARK';
		} elseif ($markedTestCases>0 && $unmarkedTestCases>0) {

			//Some testCases are marked and others unmarked
			$testCategoryParent['CheckState']='UNDEFINED';
		} elseif ($markedTestCases==0 && $unmarkedTestCases==0){
			//No testcases detected
			//All child categories unmarked
			$testCategoryParent['CheckState']='NOMARK';
		}
	}

	//print("Final Parent testCategory with id ".$testCategoryParent['ID']." result: ".$testCategoryParent['CheckState']."\n");
	//print("----------------------------------\n");
	//Add the result to the global array
	$resultArray[]=$testCategoryParent;
	//Then, return their actual state
	return $testCategoryParent['CheckState'];

}
?>
