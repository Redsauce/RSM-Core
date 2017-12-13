<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$groupID = $GLOBALS['RS_POST']['groupID'];
$subjectID = $GLOBALS['RS_POST']['subjectID'];
$roundID = $GLOBALS['RS_POST']['roundID'];
$parentTestCaseID = $GLOBALS['RS_POST']['parentTestCaseID'];

// get the item type and the properties
// get the item types
$groupsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);
$categoriesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$testcasesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
$itemTypeRelationsID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

// get properties
//Group properties
$groupMainPropertyID = getMainPropertyID($groupsItemTypeID, $clientID);

//Category properties
$categoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$categoryGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$categoryOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryOrder'], $clientID);
$categoryNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryName'], $clientID);

//Relations properties
$relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
$relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
$relationsTestCasesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
$relationsTestCategoriesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);

//test cases properties
$testCasesCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);
$testcasesNamePropertyID =getClientPropertyID_RelatedWith_byName($definitions['testcasesName'], $clientID);
$testcasesOrderPropertyID =getClientPropertyID_RelatedWith_byName($definitions['testcasesOrder'], $clientID);

//Whe ned get all the testcases associated to this group and with parentTestCase like the passed

$returnProperties = array();
$returnProperties[] = array('ID' => $categoryParentPropertyID, 'name' => 'testCategoryParentTestCategoryID');
$returnProperties[] = array('ID' => $categoryNamePropertyID, 'name' => 'testCategoryName');
$returnProperties[] = array('ID' => $categoryGroupPropertyID, 'name' => 'testCategoryGroupID');
$returnProperties[] = array('ID' => $categoryOrderPropertyID, 'name' => 'testCategoryOrder');

$filters = array();
$filters[] = array('ID' => $categoryParentPropertyID, 'value' => $parentTestCaseID);
if($parentTestCaseID=="0"){
	$filters[] = array('ID' => $categoryGroupPropertyID, 'value' => $groupID);
}

$categories = getFilteredItemsIDs($categoriesItemTypeID, $clientID, $filters, $returnProperties);

//Next, order the categories
usort($categories, make_comparer('testCategoryOrder'));

//Init the result array
$result = array();

//Next, get the ids for the test cases that are related
$returnProperties = array();
$returnProperties[] = array('ID' => $relationsRoundPropertyID, 'name' => 'roundID');
$returnProperties[] = array('ID' => $relationsSubjectPropertyID, 'name' => 'subjectID');
$returnProperties[] = array('ID' => $relationsTestCasesPropertyID, 'name' => 'testCasesIDs');
$returnProperties[] = array('ID' => $relationsTestCategoriesPropertyID, 'name' => 'testCatIDs');

//build the filter
$filters = array();
$filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $roundID);
$filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $subjectID);

$relations = getFilteredItemsIDs($itemTypeRelationsID, $clientID, $filters, $returnProperties);

if (count($relations)==1){
	//Next, check if the category is in the relations. If yes, add it to the results array.
	$relatedTestCategories = explode(',',$relations[0]['testCatIDs']);

	foreach ($categories as $cat){
	  if (in_array($cat['ID'],$relatedTestCategories)){
		$partRes = array();
		$partRes['ID'] = $cat['ID'];
		$partRes['name'] = $cat['testCategoryName'];
		$partRes['type'] = 'testCategory';
		$partRes['parentTestCategory']=$cat['testCategoryParentTestCategoryID'];
		$partRes['parentGroupID']=$cat['testCategoryGroupID'];
		$partRes['order']=$cat['testCategoryOrder'];
		$result[]=$partRes;
	  }
	}

	//Get the test cases ids inside an array
	$idsToSearch = explode(',',$relations[0]['testCasesIDs']);

	//Get the test cases and filter
	$availableTestCases = getFilteredTestCasesInsideCategory($parentTestCaseID, $idsToSearch);


	//And add to results
	for ($i=0; $i<count($availableTestCases); $i++){

		$partRes = array();
		$partRes['ID'] = $availableTestCases[$i]['ID'];
		$partRes['name'] = $availableTestCases[$i]['testcaseName'];
		$partRes['type'] = 'testCase';
		$partRes['parentTestCategory']=$availableTestCases[$i]['testCategoryParentID'];
		$partRes['parentGroupID']= -1;
		$partRes['order']=$availableTestCases[$i]['order'];

		$result[]=$partRes;
	}
}

RSReturnArrayQueryResults($result);


//******************************************************************
//This function returns all the testcases inside one parent category and their sub-categories
//******************************************************************
function getFilteredTestCasesInsideCategory($parentCategoryID, $idsToSearch){

	global $clientID,$categoriesItemTypeID,$categoryParentPropertyID,$testcasesItemTypeID,$testCasesCategoryPropertyID,$testcasesNamePropertyID,$testcasesOrderPropertyID,$inDebug;

	// build return properties array
	$returnProperties = array();
	$returnProperties[] = array('ID' => $testcasesNamePropertyID, 'name' => 'testcaseName');
	$returnProperties[] = array('ID' => $testCasesCategoryPropertyID, 'name' => 'testCategoryParentID');
	$returnProperties[] = array('ID' => $testcasesOrderPropertyID, 'name' => 'order');

	//build the filter
	$filters = array();
	$filters[] = array('ID' => $testCasesCategoryPropertyID, 'value' => $parentCategoryID);

	$testCases = getFilteredItemsIDs($testcasesItemTypeID, $clientID, $filters, $returnProperties);

	//Next, save only the test cases that are inside the relation
	$resultTC = array();
	for ($i=0;$i<count($testCases);$i++){
		foreach ($idsToSearch as $rel){
			if ($testCases[$i]['ID']==$rel){
				//test found in relation, add to returning array
				$resultTC[]=$testCases[$i];
				break;
			}
		}
	}

	//And return the testCases
	return($resultTC);
}
?>
