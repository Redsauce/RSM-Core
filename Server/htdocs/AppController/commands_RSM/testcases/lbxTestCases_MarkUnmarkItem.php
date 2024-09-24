<?php
//***************************************************
//Description:
//	 Mark or unmark groups, categories or testcases
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMtestsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$roundID = $GLOBALS['RS_POST']['roundID'];
$subjectID = $GLOBALS['RS_POST']['subjectID'];
$itemID = $GLOBALS['RS_POST']['itemID'];
$isGroup = $GLOBALS['RS_POST']['isGroup'];
$isCategory = $GLOBALS['RS_POST']['isCategory'];
$mode = $GLOBALS['RS_POST']['mode'];

//First, we need get the objects that has the roundID and subjectID
$relations = getRelations($roundID, $subjectID, $clientID);

$result['result'] = 'NOK';

for ($i = 0; $i < count($relations); $i++) {
    //Update the item checked
    $valResult = processItem($relations[$i], $clientID, $itemID, $isGroup, $isCategory, $mode, $subjectID, $roundID);
    if ($valResult == 0) {
        $result['result'] = 'OK';
    } else {
        $result['result'] = 'NOK';
    }
}

RSReturnArrayResults($result);

//******************************************************************
//This function marks or unmarks an item that could be a Group, Category or Single testCase
//******************************************************************
function processItem($relation, $clientID, $itemID, $isGroup, $isCategory, $mode, $subjectID, $roundID) {

    global $definitions;

    // get item types
    $itemTypeTestCasesID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
    $itemTypeRelationsID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

    // get properties
    $relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
    $relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
    $relationsTestCasesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
    $relationsTestCategoriesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);
    $testCategoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
    $testCasesParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);

    //First, for prevent errors, clear empty values inside the array
    $prevTCatIds = explode(',', $relation['testCatIDs']);
    $prevTCasesIds = explode(',', $relation['testCasesIDs']);
    $prevTCatIds = array_filter($prevTCatIds);
    $prevTCasesIds = array_filter($prevTCasesIds);
    $relation['testCatIDs'] = implode(',', $prevTCatIds);
    $relation['testCasesIDs'] = implode(',', $prevTCasesIds);

    if ($mode == "Mark") {
        //Mark item and childs if necessary
        if ($isGroup == "True") {
            //is a group. We need get all their categories
            $testCategoriesToManage = getAllTestCategoriesInsideAGroup($itemID, $clientID);

            //Search the new values and add the testcase
            foreach ($testCategoriesToManage as $theTestCategoryID) {
                //Update the items TestCategories with the ids that aren't in the relation
                $relation['testCatIDs'] = addItemsToRelationIfNotExists($relation['testCatIDs'], $theTestCategoryID);
            }

            //Next, we need all the testcases inside a group
            $testCasesToManage = getAllTestCasesInsideAGroup($itemID, $clientID);

            //Foreach testCase, check if exists in the relation
            foreach ($testCasesToManage as $theTestCaseID) {

                if (!in_array($theTestCaseID, $prevTCasesIds)) {
                    //Create first a copy of their steps
                    //and add the relation
                    duplicateStepsForTestCase($theTestCaseID, $relation, $clientID);

                    //Update the item testCase
                    $relation['testCasesIDs'] = addItemsToRelationIfNotExists($relation['testCasesIDs'], $theTestCaseID);
                }

                //Search inside the automatedExecutions and create the relation if necessary
                //Get the parentFolder
                $parentFolderID = getItemPropertyValue($theTestCaseID, $testCasesParentPropertyID, $clientID);
            }

            //Finally, update in the database the test cases relations
            setPropertyValueByID($relationsTestCasesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCasesIDs']);

            //and the test Categories relations
            setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCatIDs']);

            //Return result OK
            return 0;

        } elseif ($isCategory == "True") {
            //is a testCategory. We need get all their child categories
            $testCategoriesToManage = getAllTestCategoriesInsideACategory($itemID, $clientID);

            //Search the new values and add the testcategory
            foreach ($testCategoriesToManage as $theTestCategoryID) {
                //Get the parent test categories for this category
                $parentTCInversedList = getParentCategoriesForCategory($theTestCategoryID, $clientID);

                //Update the items TestCategories with the ids that aren't in the relation
                $relation['testCatIDs'] = addItemsToRelationIfNotExists($relation['testCatIDs'], implode(',', $parentTCInversedList));
            }

            //Next, we need all the testcases inside a group
            $testCasesToManage = getAllTestCasesInsideCategory($itemID, $clientID);

            //Foreach testCase, check if exists in the relation
            foreach ($testCasesToManage as $theTestCaseID) {
                if (!in_array($theTestCaseID, $prevTCasesIds)) {
                    //Create first a copy of their steps
                    //and add the relation
                    duplicateStepsForTestCase($theTestCaseID, $relation, $clientID);

                    //Update the item testCase
                    $relation['testCasesIDs'] = addItemsToRelationIfNotExists($relation['testCasesIDs'], $theTestCaseID);
                }

                //Search inside the automatedExecutions and create the relation if necessary
                //Get the parentFolder
                $parentFolderID = getItemPropertyValue($theTestCaseID, $testCasesParentPropertyID, $clientID);
            }

            //Finally, update in the database the test cases relations
            setPropertyValueByID($relationsTestCasesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCasesIDs']);

            //and the test Categories relations
            setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCatIDs']);

            //Return result OK
            return 0;

        } else {
            //is a test cases. Directly add if previously isn't in the item
            if (!in_array($itemID, $prevTCasesIds)) {
                //We need add the new testCase to the relation
                //Create first a copy of their steps and add the relation
                //Get the testCase that has this id
                $theTestCaseForProcess = getItems($itemTypeTestCasesID, $clientID, true, $itemID);

                if ($theTestCaseForProcess != NULL) {
                    duplicateStepsForTestCase($theTestCaseForProcess[0]['ID'], $relation, $clientID);
                }

                //Get the parent test categories for this testcase
                $parentTCInversedList = getParentCategoriesForTestCase($itemID, $clientID);

                //Update the items TestCategories with the ids that aren't in the relation
                $relation['testCatIDs'] = addItemsToRelationIfNotExists($relation['testCatIDs'], implode(',', $parentTCInversedList));

                //Add the id and update the property
                $relation['testCasesIDs'] = addItemsToRelationIfNotExists($relation['testCasesIDs'], $itemID);

                //Update the item
                setPropertyValueByID($relationsTestCasesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCasesIDs']);

                //and the test Categories relations
                setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCatIDs']);

                //Update the executionsrelations
                //first, get the parent testcategory associated to the testcase
                $parentFolderID = getItemPropertyValue($itemID, $testCasesParentPropertyID, $clientID);

                //Return result OK
                return 0;
            }
        }
    } else {
        //Unmark item and childs if necessary
        if ($isGroup == "True") {
            //is a group. We need get all their categories
            $testCategoriesToManage = getAllTestCategoriesInsideAGroup($itemID, $clientID);

            foreach ($testCategoriesToManage as $theTestCategoryID) {
                //Search the values and delete it
                if (in_array($theTestCategoryID, $prevTCatIds)) {
                    //Clear the value
                    $relation['testCatIDs'] = removeItemsFromRelationIfExists($relation['testCatIDs'], $theTestCategoryID);
                }
            }

            //Next, we need all the testcases inside a group
            $testCasesToManage = getAllTestCasesInsideAGroup($itemID, $clientID);

            //Foreach testCase, check if exists in the relation
            foreach ($testCasesToManage as $theTestCaseID) {
                //Search the values and delete it
                if (in_array($theTestCaseID, $prevTCasesIds)) {
                    //Clear the steps in results
                    $relation['testCasesIDs'] = removeItemsFromRelationIfExists($relation['testCasesIDs'], $theTestCaseID);

                    deleteStepsResultsForATestCase($theTestCaseID, $relation, $clientID);
                }
            }

            //Finally, update in the database the testCases relations
            setPropertyValueByID($relationsTestCasesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCasesIDs']);

            //and the tests categories relations
            setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCatIDs']);

            //Return result OK
            return 0;

        } elseif ($isCategory == "True") {

            //is a testCategory. We need get all their child categories
            $testCategoriesToManage = getAllTestCategoriesInsideACategory($itemID, $clientID);

            //get all tests inside categories
            $testCasesToManage = array();
            foreach ($testCategoriesToManage as $theTestCategoryID) {
                $testCasesToManage = array_merge($testCasesToManage, getAllTestCasesInsideCategory($theTestCategoryID, $clientID));
            }

            //remove duplicates
            $testCasesToManage = array_unique($testCasesToManage);

            //get parent categories in descending order
            $parentCategories = getParentCategoriesForCategory($itemID, $clientID);

            //Return result
            return deleteTestsTree($testCasesToManage, $testCategoriesToManage, $parentCategories, $relation, $prevTCatIds, $prevTCasesIds, $subjectID, $roundID, $clientID, $itemTypeRelationsID, $relationsTestCasesPropertyID, $relationsTestCategoriesPropertyID);

        } else {
            //is a test case. Search and remove the entry
            $testCasesToManage = array();
            $testCasesToManage[] = $itemID;

            $testCategoriesToManage = array();

            //get parent categories in descending order
            $parentCategories = getParentCategoriesForTestCase($itemID, $clientID);

            //Return result
            return deleteTestsTree($testCasesToManage, $testCategoriesToManage, $parentCategories, $relation, $prevTCatIds, $prevTCasesIds, $subjectID, $roundID, $clientID, $itemTypeRelationsID, $relationsTestCasesPropertyID, $relationsTestCategoriesPropertyID);
        }
    }
    return -1;
}

//******************************************************************
//This function deletes passed tests & categories and all possible parent categories (not needed for other items in relation)
//******************************************************************
function deleteTestsTree($testCasesToManage, $testCategoriesToManage, $parentCategories, $relation, $prevTCatIds, $prevTCasesIds, $subjectID, $roundID, $clientID, $itemTypeRelationsID, $relationsTestCasesPropertyID, $relationsTestCategoriesPropertyID) {

    //for each parent category check if needed or can be deleted
    for ($i = 0; $i < count($parentCategories); $i++) {
        $childCategories = getTestCategoriesInsideACategory($parentCategories[$i], $clientID);
        $childTestCases = getTestCasesInsideCategory($parentCategories[$i], $clientID);
        //check any other child in relation
        $isNeeded = false;
        foreach ($childCategories as $theChildCategoryID) {
            //if the subcategory is in relation but not marked to delete stop removing parents
            if (in_array($theChildCategoryID, $prevTCatIds) && !in_array($theChildCategoryID, $testCategoriesToManage)) {
                $isNeeded = true;
                break;
            }
        }
        if (!$isNeeded) {
            foreach ($childTestCases as $theChildTestCaseID) {
                //if the ChildTestCase is in relation but not marked to delete stop removing parents
                if (in_array($theChildTestCaseID, $prevTCasesIds) && !in_array($theChildTestCaseID, $testCasesToManage)) {
                    $isNeeded = true;
                    break;
                }
            }
        }
        if (!$isNeeded) {
            //add parent to delete list
            $testCategoriesToManage[] = $parentCategories[$i];
        } else {
            //parent needed, not add and stop checking lower levels
            break;
        }
    }

    //Next, delete the categories if exist
    //Update the testsCategories in the relation
    $relation['testCatIDs'] = removeItemsFromRelationIfExists($relation['testCatIDs'], implode(',', $testCategoriesToManage));

    //Delete the test cases if exists
    foreach ($testCasesToManage as $theTestCaseID) {
        if (in_array($theTestCaseID, $prevTCasesIds)) {
            //Clear the steps in results
            deleteStepsResultsForATestCase($theTestCaseID, $relation, $clientID);
        }
    }

    //Update the testsCases in the relation
    $relation['testCasesIDs'] = removeItemsFromRelationIfExists($relation['testCasesIDs'], implode(',', $testCasesToManage));

    //Finally, update in the database the testCases relations
    setPropertyValueByID($relationsTestCasesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCasesIDs']);

    //and the tests categories relations
    setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relation['ID'], $clientID, $relation['testCatIDs']);

    //Return result OK
    return 0;
}
?>