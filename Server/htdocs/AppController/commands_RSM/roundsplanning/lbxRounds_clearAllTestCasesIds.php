<?php
//***************************************************
//Description:
//	 Update a round associated test cases. Clear all tc
//   inside the group
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$roundID = $GLOBALS['RS_POST']['roundID'];
$groupID = $GLOBALS['RS_POST']['groupID'];

$roundAssociatedTCIDsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedTestCasesIDs'], $clientID);
$itemTypeRoundID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);

$itemTypeTestCategoryID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$mainPropertyTestCategoryID = getMainPropertyID($itemTypeTestCategoryID, $clientID);
$TestCasesGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
$parentTestCaseCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);

//First, get the actual associated test categories
$theRoundTCIds = getItemPropertyValue($roundID, $roundAssociatedTCIDsPropertyID, $clientID);

//Get all TC associated to the group
$returnPropertiesTestCategories = array();
$returnPropertiesTestCategories[] = array('ID' => $mainPropertyTestCategoryID, 'name' => 'name');
$returnPropertiesTestCategories[] = array('ID' => $TestCasesGroupPropertyID, 'name' => 'groupID');

//build the filter
$filtersTestCategories = array();
$filtersTestCategories[] = array('ID' => $TestCasesGroupPropertyID, 'value' => $groupID);

$testcasescategories = getFilteredItemsIDs($itemTypeTestCategoryID, $clientID, $filtersTestCategories, $returnPropertiesTestCategories);

$TCIdsArray = array();
$TCIdsArray = explode(',', $theRoundTCIds);

//All childs do will cleared
//Find in the tc and remove from the round tc list
for ($i = 0; $i < count($testcasescategories); $i++) {
    $keyPosition = -1;
    $keyPosition = array_search($testcasescategories[$i]['ID'], $TCIdsArray);
    if ($keyPosition > -1) {
        //Key found. Delete it.
        unset($TCIdsArray[$keyPosition]);
    }
}

//Finally, recreate the array and update the ids in the round
$TCIdsArray = array_values($TCIdsArray);

$finalAssociatedValues = '';
for ($i = 0; $i < count($TCIdsArray); $i++) {
    $finalAssociatedValues = $finalAssociatedValues . $TCIdsArray[$i];
    if ($i < count($TCIdsArray) - 1) {
        $finalAssociatedValues = $finalAssociatedValues . ',';
    }
}

setPropertyValueByID($roundAssociatedTCIDsPropertyID, $itemTypeRoundID, $roundID, $clientID, $finalAssociatedValues, '', $RSuserID);

$results['newAssociatedTCIds'] = $finalAssociatedValues;

RSReturnArrayResults($results);
?>


