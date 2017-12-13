<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$groupID = $GLOBALS['RS_POST']['groupID'];
$parentTestCaseCategoryID = $GLOBALS['RS_POST']['parentTestCaseID'];
$selectedRoundTCIds = explode(',', $GLOBALS['RS_POST']['selectedTestCasesIDs']);


// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$itemTypeTestCasesID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);

$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$groupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$parentTestCaseCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$orderPropertyID= getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryOrder'], $clientID);

$namePropertyTestCasesID =getClientPropertyID_RelatedWith_byName($definitions['testcasesName'], $clientID);
$orderTestCasesPropertyID= getClientPropertyID_RelatedWith_byName($definitions['testcasesOrder'], $clientID);
$parentTestCaseTestCategoryPropID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);



//build the filter
$filters = array();
$filters[] = array('ID' => $groupPropertyID, 'value' => $groupID);
$filters[] = array('ID' => $parentTestCaseCategoryPropertyID, 'value' => $parentTestCaseCategoryID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'name');
$returnProperties[] = array('ID' => $groupPropertyID, 'name' => 'groupID');
$returnProperties[] = array('ID' => $parentTestCaseCategoryPropertyID, 'name' => 'parentTCID');
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');

$categories = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties, 'order');

// filter categories
$orderedCategories = array();

for ($i = 0; $i < count($categories); $i++) {
	// update entry
	$categories[$i]['isCategory']='True';
	
	// get category subtree
	$categoryTree = getItemsTree(
		$itemTypeID, 
		$clientID, 
		$parentTestCaseCategoryPropertyID, 
		$categories[$i]['ID'],
		array(array('ID' => $groupPropertyID, 'value' => $categories[$i]['groupID']))
	);
	
	// save categories into an array
	$allCategories = array();
	$allCategories[] = $categories[$i]['ID'];
	
	if ($categoryTree != null) {
		foreach ($categoryTree as $parentCategory) {
			foreach ($parentCategory as $child) {
				$allCategories[] = $child['ID'];
			}
		}
	}
	
	// get categories test cases
	$testcases = getFilteredItemsIDs(
		$itemTypeTestCasesID,
		$clientID,
		array(array('ID' => $parentTestCaseTestCategoryPropID, 'value' => implode(',', $allCategories), 'mode' => '<-IN')),
		array()
	);
	
	foreach ($testcases as $testcase) {
		if (in_array($testcase['ID'], $selectedRoundTCIds)) {
			// the category must be shown
			$orderedCategories[] = $categories[$i];
			break;
		}
	}
}



//Next, get the test cases associated to this category
//build the filter
$filters = array();
$filters[] = array('ID' => $parentTestCaseTestCategoryPropID, 'value' => $parentTestCaseCategoryID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $namePropertyTestCasesID, 'name' => 'name');
$returnProperties[] = array('ID' => $orderTestCasesPropertyID, 'name' => 'order');

$allCategoryTestCases = getFilteredItemsIDs($itemTypeTestCasesID, $clientID, $filters, $returnProperties, 'order');

$orderedTestCases = array();
foreach ($allCategoryTestCases as $testcase) {
	if (in_array($testcase['ID'], $selectedRoundTCIds)) {
		$orderedTestCases[] = $testcase;
	} 	
}

//Finally, for every result, add the group and a value that marks that isn't a category
for ($i=0; $i<count($orderedTestCases); $i++){
	$orderedTestCases[$i]['groupID']=$groupID ;
	$orderedTestCases[$i]['isCategory']='False';
	$orderedTestCases[$i]['parentTCID']=$parentTestCaseCategoryID;
}


// Merge all the results and return it
$theResult = array_merge($orderedCategories, $orderedTestCases);

RSReturnArrayQueryResults($theResult);
?>