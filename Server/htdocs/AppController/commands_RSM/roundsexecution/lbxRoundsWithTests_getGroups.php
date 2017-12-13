<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$studyID = $GLOBALS['RS_POST']['studyID'];
$roundsAssociatedTCIds = explode(',', $GLOBALS['RS_POST']['roundsAssociatedTCIds']);


// get the item types
$groupsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);
$categoriesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$testcasesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);

// get properties
$groupMainPropertyID = getMainPropertyID($groupsItemTypeID, $clientID);
$groupStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['groupsStudyID'], $clientID);
$categoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
$categoryGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$testCasesCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);



// get the study groups
$groups = IQ_getFilteredItemsIDs(
	$groupsItemTypeID,
	$clientID,
	array(array('ID' => $groupStudyPropertyID, 'value' => $studyID)),
	array(array('ID' => $groupMainPropertyID, 'name' => 'name'))
);

// prepare filteredGroups array
$filteredGroups = array();

while ($group = $groups->fetch_assoc()) {
	// get group categories	
	$categories = getItemsTree(
		$categoriesItemTypeID, 
		$clientID, 
		$categoryParentPropertyID, 
		'0',
		array(array('ID' => $categoryGroupPropertyID, 'value' => $group['ID']))
	);
	
	// initialize test cases array
	$testcases = array();
	
	if ($categories != null) { 
		// save all subcategories into an array
		$allCategories = array();
		foreach ($categories as $parentCategory) {
			foreach ($parentCategory as $child) {
				$allCategories[] = $child['ID'];
			}
		}
	
		// get categories test cases
		$testcases = getFilteredItemsIDs(
			$testcasesItemTypeID,
			$clientID,
			array(array('ID' => $testCasesCategoryPropertyID, 'value' => implode(',', $allCategories), 'mode' => '<-IN')),
			array()
		);	
	}
	
	foreach ($testcases as $testcase) {
		if (in_array($testcase['ID'], $roundsAssociatedTCIds)) {
			// the group must be shown
			$filteredGroups[] = $group;
			break;
		}	
	}
}

// return results
RSReturnArrayQueryResults($filteredGroups);
?>