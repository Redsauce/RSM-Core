<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$groupID = $GLOBALS['RS_POST']['groupID'];
$parentTestCaseCategoryID = $GLOBALS['RS_POST']['parentCategoryID'];

$groupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$parentTestCaseCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$orderPropertyID= getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryOrder'], $clientID);

// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'name');
$returnProperties[] = array('ID' => $groupPropertyID, 'name' => 'groupID');
$returnProperties[] = array('ID' => $parentTestCaseCategoryPropertyID, 'name' => 'parentCategoryID');
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');


//build the filter
$filters = array();
//$filters[] = array('ID' => $groupPropertyID, 'value' => $groupID);
$filters[] = array('ID' => $parentTestCaseCategoryPropertyID, 'value' => $parentTestCaseCategoryID);

$testcasescategories = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

//Reorder results by order
usort($testcasescategories, makeComparer('order'));

// get the test cases
$tcItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
$categoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);
$tcOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesOrder'], $clientID);

$filteredProperties = array();
$filteredProperties[] = array('ID' => $categoryPropertyID, 'value' => $parentTestCaseCategoryID);

$returnProperties = array();
$returnProperties[] = array('ID' => getMainPropertyID($tcItemTypeID, $clientID), 'name' => 'name');
$returnProperties[] = array('ID' => $categoryPropertyID, 'name' => 'parentCategoryID');
$returnProperties[] = array('ID' => $tcOrderPropertyID, 'name' => 'order');

$testcases = getFilteredItemsIDs($tcItemTypeID, $clientID, $filteredProperties, $returnProperties);

foreach ($testcases as $testcase) {
	$testcasescategories[] = $testcase;
}

RSReturnArrayQueryResults($testcasescategories);
?>
