<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$studyID = $GLOBALS['RS_POST']['studyID'];
$parentTestCaseCategoryID = $GLOBALS['RS_POST']['parentCategoryID'];

//First, we need get all the groups associated with this study
$parentStudyID = getClientPropertyID_RelatedWith_byName($definitions['groupsStudyID'], $clientID);
// get the item type and the properties
$itemTypeGroupID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);
$mainPropertyGroupID = getMainPropertyID($itemTypeGroupID, $clientID);


$TestCasesGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$parentTestCaseCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$orderTestCategoryPropertyID= getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryOrder'], $clientID);
// get the item type and the properties
$itemTypeTestCategoryID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$mainPropertyTestCategoryID = getMainPropertyID($itemTypeTestCategoryID, $clientID);


// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyGroupID, 'name' => 'mainValue');

//build an empty filter
$filters = array();
$filters[] = array('ID' => $parentStudyID, 'value' => $studyID);

$groups = getFilteredItemsIDs($itemTypeGroupID, $clientID, $filters, $returnProperties);


$results = array();

//When we have all the groups, we need get the test cases categories for every group
for ($i=0;$i<count($groups);$i++){
	//Add the group entry
	$groups[$i]['isAGroup']='True';



	// build return properties array
	$returnPropertiesTestCategories = array();
	$returnPropertiesTestCategories[] = array('ID' => $mainPropertyTestCategoryID, 'name' => 'name');
	$returnPropertiesTestCategories[] = array('ID' => $TestCasesGroupPropertyID, 'name' => 'groupID');
	$returnPropertiesTestCategories[] = array('ID' => $parentTestCaseCategoryPropertyID, 'name' => 'parentCategoryID');
	$returnPropertiesTestCategories[] = array('ID' => $orderTestCategoryPropertyID, 'name' => 'order');


	//build the filter
	$filtersTestCategories = array();
	$filtersTestCategories[] = array('ID' => $TestCasesGroupPropertyID, 'value' => $groups[$i]['ID']);
	$filtersTestCategories[] = array('ID' => $parentTestCaseCategoryPropertyID, 'value' => $parentTestCaseCategoryID);

	$testcasescategories = getFilteredItemsIDs($itemTypeTestCategoryID, $clientID, $filtersTestCategories, $returnPropertiesTestCategories);

	//Reorder results
	usort($testcasescategories, make_comparer('order'));

	//Add the group first
	array_push($results,(array)$groups[$i]);

	for ($j=0; $j<count($testcasescategories); $j++)
	{
		$testcasescategories[$j]['isAGroup']='False';
		array_push($results,(array)$testcasescategories[$j]);
	}
	//Append the result to the group
	//$groups[$i]['associatedTestCategories']=$orderedCategories;
}

RSReturnArrayQueryResults($results);

?>
