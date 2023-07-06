<?php
require_once "RSdatabase.php";
require_once "RSMitemsManagement.php";

//******************************************************************
//Get relations for the round and subject passed
//******************************************************************
function getRelations($theRoundID, $theSubjectID, $clientID)
{
    global $definitions;

    //get item type
    $relationsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['roundSubjectsTestRelations'], $clientID);

    //get properties
    $relationsRoundPropertyID          = getClientPropertyIDRelatedWithByName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
    $relationsSubjectPropertyID        = getClientPropertyIDRelatedWithByName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
    $relationsTestCasesPropertyID      = getClientPropertyIDRelatedWithByName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
    $relationsTestCategoriesPropertyID = getClientPropertyIDRelatedWithByName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);

    //First get the relation associated
    $returnProperties = array();
    $returnProperties[] = array('ID' => $relationsRoundPropertyID, 'name' => 'roundID');
    $returnProperties[] = array('ID' => $relationsSubjectPropertyID, 'name' => 'subjectID');
    $returnProperties[] = array('ID' => $relationsTestCasesPropertyID, 'name' => 'testCasesIDs');
    $returnProperties[] = array('ID' => $relationsTestCategoriesPropertyID, 'name' => 'testCatIDs');

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $theRoundID);
    $filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $theSubjectID);

    return getFilteredItemsIDs($relationsItemTypeID, $clientID, $filters, $returnProperties);
}

//******************************************************************
///This function returns all the testcases inside one group (and inside their categories and sub-categories)
//******************************************************************
function getAllTestCategoriesInsideAGroup($groupID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesCategoriesID = getClientItemTypeIDRelatedWithByName($definitions['testcasescategory'], $clientID);

    //get property
    $testCategoryParentGroupID = getClientPropertyIDRelatedWithByName($definitions['testcasescategoryGroupID'], $clientID);

    //First, we need get all the categories that has the parent groupID
    // build return properties array
    $returnProperties = array();

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $testCategoryParentGroupID, 'value' => $groupID);

    $testCategories = getFilteredItemsIDs($itemTypeTestCasesCategoriesID, $clientID, $filters, $returnProperties);

    //Get only the testCategories ids
    $onlyIds = array();

    foreach ($testCategories as $tcat) {
        $onlyIds[] = $tcat['ID'];
    }

    return $onlyIds;
}

//******************************************************************
//This function returns all the testcases inside one group (and inside their categories and sub-categories)
//******************************************************************
function getAllTestCasesInsideAGroup($groupID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesID = getClientItemTypeIDRelatedWithByName($definitions['testcases'], $clientID);

    //get property
    $testCasesParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasesFolderID'], $clientID);

    //get all categories inside group
    $onlyIds = getAllTestCategoriesInsideAGroup($groupID, $clientID);

    //Next, create a string with all the categories inside the group
    $toFilter = implode(',', $onlyIds);

    //Create the filter
    // build return properties array
    $returnProperties = array();

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $testCasesParentPropertyID, 'value' => $toFilter, 'mode' => '<-IN');

    $allTestCases = getFilteredItemsIDs($itemTypeTestCasesID, $clientID, $filters, $returnProperties);

    //Get only the testCategories ids
    $onlyIds = array();

    foreach ($allTestCases as $tcas) {
        $onlyIds[] = $tcas['ID'];
    }

    return $onlyIds;
}

//******************************************************************
//This function returns all the categories inside one parent category and their sub-categories
//******************************************************************
function getAllTestCategoriesInsideACategory($parentCategoryID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesCategoriesID = getClientItemTypeIDRelatedWithByName($definitions['testcasescategory'], $clientID);

    //get property
    $testCategoryParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasescategoryParentID'], $clientID);

    //First, we need the tree categories
    $tree = getItemsTree($itemTypeTestCasesCategoriesID, $clientID, $testCategoryParentPropertyID, $parentCategoryID);

    //Transform the items tree and store the ids in an unidimensional array
    $allCategories = array();

    //First, add the parentCategoryID
    $allCategories[] = $parentCategoryID;

    if ($tree) {
        foreach ($tree as $parid => $parent) {

            if (!in_array($parid, $allCategories)) {
                //Add the value
                $allCategories[] = $parid;
            }

            foreach ($parent as $child) {
                $id = $child['ID'];

                //Check if the values does not exist in the allCategories array.
                if (!in_array($id, $allCategories)) {
                    //Add the value
                    $allCategories[] = $id;
                }
            }
        }
    }

    //And return the testCategories
    return ($allCategories);
}

//******************************************************************
//This function returns the categories inside one parent category only (not sub-categories)
//******************************************************************
function getTestCategoriesInsideACategory($parentCategoryID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesCategoriesID = getClientItemTypeIDRelatedWithByName($definitions['testcasescategory'], $clientID);

    //get property
    $testCategoryParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasescategoryParentID'], $clientID);

    //Create the filter
    // build return properties array
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $testCategoryParentPropertyID, 'value' => $parentCategoryID);

    $testCategories = getFilteredItemsIDs($itemTypeTestCasesCategoriesID, $clientID, $filters, $returnProperties);

    //Get only the testCategories ids
    $onlyIds = array();

    foreach ($testCategories as $tcat) {
        $onlyIds[] = $tcat['ID'];
    }

    return $onlyIds;
}

//******************************************************************
//This function returns all the testcases inside one parent category and their sub-categories
//******************************************************************
function getAllTestCasesInsideCategory($parentCategoryID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesID = getClientItemTypeIDRelatedWithByName($definitions['testcases'], $clientID);

    //get property
    $testCasesParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasesFolderID'], $clientID);

    //get all categories inside
    $allCategories = getAllTestCategoriesInsideACategory($parentCategoryID, $clientID);

    $toFilter = implode(',', $allCategories);

    //When we have all the categories inside, get their test cases
    //Create the filter
    // build return properties array
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $testCasesParentPropertyID, 'value' => $toFilter, 'mode' => '<-IN');

    $testCases = getFilteredItemsIDs($itemTypeTestCasesID, $clientID, $filters, $returnProperties);

    //Get only the testCategories ids
    $onlyIds = array();

    foreach ($testCases as $tcas) {
        $onlyIds[] = $tcas['ID'];
    }

    return $onlyIds;
}

//******************************************************************
//This function returns the testcases inside one parent category only (not sub-categories)
//******************************************************************
function getTestCasesInsideCategory($parentCategoryID, $clientID)
{

    global $definitions;

    //get item type
    $itemTypeTestCasesID = getClientItemTypeIDRelatedWithByName($definitions['testcases'], $clientID);

    //get property
    $testCasesParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasesFolderID'], $clientID);

    // build return properties array
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $testCasesParentPropertyID, 'value' => $parentCategoryID);

    $testCases = getFilteredItemsIDs($itemTypeTestCasesID, $clientID, $filters, $returnProperties);

    //Get only the testCategories ids
    $onlyIds = array();

    foreach ($testCases as $tcas) {
        $onlyIds[] = $tcas['ID'];
    }

    return $onlyIds;
}

//******************************************************************
//Get the structure from the testCategories to the first test category. Returns all inversed testCategories tree
//******************************************************************
function getParentCategoriesForCategory($categoryID, $clientID)
{

    global $definitions;

    //get item type
    //$itemTypeTestCasesCategoriesID = getClientItemTypeIDRelatedWithByName($definitions['testcasescategory'], $clientID);

    //get property
    $testCategoryParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasescategoryParentID'], $clientID);

    //start loop with passed category
    $aux = $categoryID;

    $categoriesArray = array();

    //search parent category until 0 level reached
    while ($aux != 0) {
        $categoriesArray[] = $aux;
        $aux = getItemPropertyValue($aux, $testCategoryParentPropertyID, $clientID);
    }

    //return categories array
    return $categoriesArray;
}

//******************************************************************
//Get the structure from the testCase to the first test category. Returns all inversed testCategories tree
//******************************************************************
function getParentCategoriesForTestCase($testCaseID, $clientID)
{

    global $definitions;

    //get item type
    // $itemTypeTestCasesID = getClientItemTypeIDRelatedWithByName($definitions['testcases'], $clientID);

    //get property
    $testCasesParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['testcasesFolderID'], $clientID);

    //First, get the parent test category for the test case
    $categoryID = getItemPropertyValue($testCaseID, $testCasesParentPropertyID, $clientID);

    //return all categories
    return getParentCategoriesForCategory($categoryID, $clientID);
}

//******************************************************************
//Adds the tests or categories that aren't in the relation
//******************************************************************
function addItemsToRelationIfNotExists($itemsList, $listToAdd)
{

    //First, split the two lists
    $existingRelation = explode(',', $itemsList);
    $toAddIds = explode(',', $listToAdd);

    //Only add if previously not exists
    for ($i = 0; $i < count($toAddIds); $i++) {

        $theAddedID = $toAddIds[$i];

        if (!in_array($theAddedID, $existingRelation)) {
            //The item does not exist
            $existingRelation[] = $theAddedID;
        }
    }

    //Finally, implode the list of categories and return
    //First, clear empty values
    $existingRelation = array_filter($existingRelation);

    return implode(',', $existingRelation);
}

//******************************************************************
//Removes the tests or categories that are in the relation
//******************************************************************
function removeItemsFromRelationIfExists($itemsList, $listToRemove)
{

    //First, split the two lists
    $existingRelation = explode(',', $itemsList);
    $toRemoveIds = explode(',', $listToRemove);

    //Only remove if previously exists
    for ($i = 0; $i < count($toRemoveIds); $i++) {

        $theRemovedPos = array_search($toRemoveIds[$i], $existingRelation);

        if ($theRemovedPos !== false) {
            //The item  exists
            unset($existingRelation[$theRemovedPos]);
        }
    }

    //Finally, implode the list of categories and return
    //First, clear empty values
    $existingRelation = array_filter($existingRelation);

    return implode(',', $existingRelation);
}

//******************************************************************
//This function removes the steps of a test case from the results
//******************************************************************
function deleteStepsResultsForATestCase($testCase, $relation, $clientID)
{

    global $definitions;

    //DEFINITIONS
    $itemTypeStepsID = getClientItemTypeIDRelatedWithByName($definitions['steps'], $clientID);
    $resultsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['result'], $clientID);

    //DEFINITIONS FOR PROPERTIES
    $tcParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsTestCaseParentID'], $clientID);
    $relatedStepPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsRelatedID'], $clientID);
    $relatedRelationPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsRoundSubjectRelationID'], $clientID);
    $stepAssocPropertyID = getClientPropertyIDRelatedWithByName($definitions['resultStepAssociatedID'], $clientID);

    //build the return array
    $returnProperties = array();

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $tcParentPropertyID, 'value' => $testCase);
    $filters[] = array('ID' => $relatedStepPropertyID, 'value' => 0, 'mode' => '<>');
    $filters[] = array('ID' => $relatedRelationPropertyID, 'value' => $relation['ID']);

    // get testcase steps
    $steps = getFilteredItemsIDs($itemTypeStepsID, $clientID, $filters, $returnProperties);

    $stepsList = array();

    foreach ($steps as $step) {
        $stepsList[] = $step['ID'];
    }

    if (!empty($stepsList)) {
        // delete steps associated results
        //build the return array
        $returnProperties = array();

        //build the filter
        $filters = array();
        $filters[] = array('ID' => $stepAssocPropertyID, 'value' => implode(',', $stepsList), 'mode' => '<-IN');

        //get results
        $res = getFilteredItemsIDs($resultsItemTypeID, $clientID, $filters, $returnProperties);

        $resList = array();

        foreach ($res as $result) {
            $resList[] = $result['ID'];
        }

        if (!empty($resList)) {
            //Clear results steps list
            deleteItems($resultsItemTypeID, $clientID, implode(',', $resList));
        }

        // finally delete steps
        deleteItems($itemTypeStepsID, $clientID, implode(',', $stepsList));
    }
}

//******************************************************************
//This function duplicate the steps of a test case
//******************************************************************
function duplicateStepsForTestCase($testCase, $relation, $clientID)
{

    global $definitions;

    //DEFINITIONS
    $itemTypeStepsID = getClientItemTypeIDRelatedWithByName($definitions['steps'], $clientID);

    //DEFINITIONS FOR PROPERTIES
    $tcParentPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsTestCaseParentID'], $clientID);
    $relatedStepPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsRelatedID'], $clientID);
    $relatedRelationPropertyID = getClientPropertyIDRelatedWithByName($definitions['stepsRoundSubjectRelationID'], $clientID);

    //First, duplicate the steps inside the test case and set the new relation
    //build the return array
    $returnProperties = array();

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $tcParentPropertyID, 'value' => $testCase);
    $filters[] = array('ID' => $relatedStepPropertyID, 'value' => 0);

    // get testcase steps
    $steps = getFilteredItemsIDs($itemTypeStepsID, $clientID, $filters, $returnProperties);

    foreach ($steps as $step) {
        // make a copy of the step
        $stepCopy = duplicateItem($itemTypeStepsID, $step['ID'], $clientID);

        // change some properties
        setPropertyValueByID($relatedStepPropertyID, $itemTypeStepsID, $stepCopy, $clientID, $step['ID'], '', $RSuserID);
        setPropertyValueByID($relatedRelationPropertyID, $itemTypeStepsID, $stepCopy, $clientID, $relation['ID'], '', $RSuserID);
    }
}
