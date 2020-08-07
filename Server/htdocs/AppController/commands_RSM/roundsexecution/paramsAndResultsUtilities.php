<?php
require_once "../utilities/RSMlistsManagement.php";

//---------------------------------------------------------------------------------------------------
//Function that gets the results for a step
function getParamsAndResultsForAStep($step, $parentStudyID, $parentSubjectID, $markedStepsUnitsIDs, $clientID) {

    global $definitions;

    //we need get the associated parameters and their result
    $itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

    $parentStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsStepParentID'], $clientID);
    $unitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);
    $conversionValuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsConversionValue'], $clientID);
    $parentStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsParentStudy'], $clientID);
    $isGlobalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsIsGlobal'], $clientID);
    $valuesListPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsValuesList'], $clientID);

    $mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
    $returnProperties[] = array('ID' => $unitPropertyID, 'name' => 'unit');
    $returnProperties[] = array('ID' => $conversionValuePropertyID, 'name' => 'conversionValue');
    $returnProperties[] = array('ID' => $parentStudyPropertyID, 'name' => 'studyID');
    $returnProperties[] = array('ID' => $isGlobalPropertyID, 'name' => 'isGlobal');
    $returnProperties[] = array('ID' => $valuesListPropertyID, 'name' => 'valuesList');

    //build the filter for all the units that are global to the study
    $filters = array();
    $filters[] = array('ID' => $parentStudyPropertyID, 'value' => $parentStudyID);
    $filters[] = array('ID' => $isGlobalPropertyID, 'value' => 1);

    $stepUnits = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

    //Seguidamente, cogemos las unidades pr√≥pias del step
    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
    $returnProperties[] = array('ID' => $unitPropertyID, 'name' => 'unit');
    $returnProperties[] = array('ID' => $conversionValuePropertyID, 'name' => 'conversionValue');
    $returnProperties[] = array('ID' => $parentStudyPropertyID, 'name' => 'studyID');
    $returnProperties[] = array('ID' => $isGlobalPropertyID, 'name' => 'isGlobal');
    $returnProperties[] = array('ID' => $valuesListPropertyID, 'name' => 'valuesList');

    //build the filter for all the units that are global to the study
    $filters = array();
    $filters[] = array('ID' => $parentStudyPropertyID, 'value' => $parentStudyID);
    $filters[] = array('ID' => $parentStepPropertyID, 'value' => $step['stepRelated']);
    $filters[] = array('ID' => $isGlobalPropertyID, 'value' => 0);

    $noGlobalStepUnits = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

    //Juntamos los dos arrays
    $stepUnits = array_merge($stepUnits, $noGlobalStepUnits);

    //a√±adimos m√°s informaciones a los stepUnits
    for ($i = 0; $i < count($stepUnits); $i++) {
        $stepUnits[$i]['parentStepID'] = $step['ID'];
        $stepUnits[$i]['isStep'] = 'False';
        $stepUnits[$i]['order'] = $i;
        $stepUnits[$i]['unit'] = base64_encode($stepUnits[$i]['unit']);
        $stepUnits[$i]['mainValue'] = base64_encode($stepUnits[$i]['mainValue']);
        $stepUnits[$i]['systemConversionValue'] = base64_encode(getAppRelatedListUnitValue($stepUnits[$i]['conversionValue']));
        $stepUnits[$i]['conversionValue'] = base64_encode($stepUnits[$i]['conversionValue']);

    }

    //a√±adimos el valor de conversi√≥n de la lista de tipos de valores

    $stepUnitsChecked = array();
    for ($i = 0; $i < count($stepUnits); $i++) {
        if (in_array($stepUnits[$i]['ID'], $markedStepsUnitsIDs)) {
            $stepUnitsChecked[] = $stepUnits[$i];
        }
    }

    //Seguidamente, hemos de coger el order que tiene cada una de las unidades dentro del estudio
    $orderUnitsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['orderUnits'], $clientID);
    $orderUnitsStepPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsStepID'], $clientID);
    $orderUnitsUnitPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsUnitID'], $clientID);
    $orderUnitsOrderPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsOrder'], $clientID);

    // build return properties array
    $returnPropertiesOrder = array();
    $returnPropertiesOrder[] = array('ID' => $orderUnitsOrderPropID, 'name' => 'order');
    $returnPropertiesOrder[] = array('ID' => $orderUnitsUnitPropID, 'name' => 'unitID');

    //build the filter
    $filtersOrder = array();
    $filtersOrder[] = array('ID' => $orderUnitsStepPropID, 'value' => $step['ID']);

    $OrderUnits = getFilteredItemsIDs($orderUnitsItemTypeID, $clientID, $filtersOrder, $returnPropertiesOrder);

    $orders = array();
    foreach ($OrderUnits as $order) {
        $orders[$order['unitID']] = $order['order'];
    }

    for ($i = 0; $i < count($stepUnitsChecked); $i++) {

        if (isset($orders[$stepUnitsChecked[$i]['ID']])) {
            $stepUnitsChecked[$i]['order'] = $orders[$stepUnitsChecked[$i]['ID']];
        } else {
            $stepUnitsChecked[$i]['order'] = 0;
        }
    }

    //Reordenamos el array en funci√≥n del orden
    usort($stepUnitsChecked, make_comparer(array('order', SORT_DESC)));

    //Una vez tenemos los par√°metros ordenados, hemos de coger sus valores
    for ($i = 0; $i < count($stepUnitsChecked); $i++) {
        $resultsValues = array();
        $resultsValues = getValuesForAStepUnit($stepUnitsChecked[$i]['ID'], $parentSubjectID, $step['ID'], $clientID);

        $stepUnitsChecked[$i]['executionValue'] = $resultsValues['executionValue'];
        $stepUnitsChecked[$i]['resultID'] = $resultsValues['resultID'];
    }

    return $stepUnitsChecked;
}

//Devuelve el valor introducido para un par√°metro en concreto
function getValuesForAStepUnit($resultStepUnitAssociatedID, $resultSubjectAssociatedID, $resultStepAssociatedID, $clientID) {

    global $definitions;

    $resultItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['result'], $clientID);
    $resultValuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultValue'], $clientID);
    $resultStepUnitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultStepUnitAssociatedID'], $clientID);
    $resultSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultSubjectAssociatedID'], $clientID);
    $resultStepAssociatedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultStepAssociatedID'], $clientID);

    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $resultValuePropertyID, 'name' => 'executionValue');
    $returnProperties[] = array('ID' => $resultStepUnitPropertyID, 'name' => 'associatedStepUnitID');

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $resultStepUnitPropertyID, 'value' => $resultStepUnitAssociatedID);
    $filters[] = array('ID' => $resultSubjectPropertyID, 'value' => $resultSubjectAssociatedID);
    $filters[] = array('ID' => $resultStepAssociatedPropertyID, 'value' => $resultStepAssociatedID);

    $results = getFilteredItemsIDs($resultItemTypeID, $clientID, $filters, $returnProperties);
    $returnedResult = array();

    if ($results) {
        //Get the latest result
        $countResults = count($results) - 1;
        $returnedResult['executionValue'] = base64_encode($results[$countResults]['executionValue']);
        $returnedResult['resultID'] = $results[$countResults]['ID'];

    } else {
        $returnedResult['executionValue'] = base64_encode(' ');
        $returnedResult['resultID'] = '0';

    }

    return $returnedResult;

}

//Cogemos el valor de sistema asociado al valor de cliente para la lista de tipos de unidades de un step
function getAppRelatedListUnitValue($clientListValue) {
    global $clientID;

    $typesListID = getAppListID('stepUnitType');

    //Get the id of the list client related
    $clientListID = getClientListID_RelatedWith($typesListID, $clientID);

    //Get all the values for this list and client
    $ClientValues = getListValues($clientListID, $clientID);

    //for every system list value, get the client list value
    for ($i = 0; $i < count($ClientValues); $i++) {
        if ($ClientValues[$i]['value'] == $clientListValue) {
            //get the client id value
            $AppValueID = getAppListValueID_RelatedWith($ClientValues[$i]['valueID'], $clientID);

            if ($AppValueID > 0) {
                //Get the app value and add to client values
                return getAppValue($AppValueID);
            } else {
                //Not related. Add an empty value
                return '';
            }
        }
    }

    //If not found, return empty value
    return '';
}

//This function returns all the testcases inside one parent category and their sub-categories
function getFilteredTestCasesInsideCategory($parentCategoryID, $idsToSearch, $clientID) {
    global $definitions;

    $testcasesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
    $testCaseParentTestCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);
    $testcasesNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesName'], $clientID);
    $testcasesOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesOrder'], $clientID);

    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $testcasesNamePropertyID, 'name' => 'testcaseName');
    $returnProperties[] = array('ID' => $testCaseParentTestCategoryPropertyID, 'name' => 'testCategoryParentID');
    $returnProperties[] = array('ID' => $testcasesOrderPropertyID, 'name' => 'order');

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $testCaseParentTestCategoryPropertyID, 'value' => $parentCategoryID);

    $testCases = getFilteredItemsIDs($testcasesItemTypeID, $clientID, $filters, $returnProperties);

    //Next, clear all the test cases that are not inside the relation
    $appliedTestCases = array();
    for ($i = 0; $i < count($testCases); $i++) {
        if (in_array($testCases[$i]['ID'], $idsToSearch)) {
            $appliedTestCases[] = $testCases[$i];
        }
    }
    //And return the testCases
    return ($appliedTestCases);
}

//Get the test case browser and steps scripts for the passed testcase
function getStepsScriptsForTestCase($theTC_ID, $theSubjectID, $roundID, $studyID, $clientID) {
    global $definitions;

    $stepsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
    $stepParentTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
    $stepDescriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsDescription'], $clientID);
    $stepOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);
    $stepCheckedStepUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);
    $stepRoundSubjectTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRoundSubjectRelationID'], $clientID);
    $stepMainPropertyID = getMainPropertyID($stepsItemTypeID, $clientID);
    $stepStepTypePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsType'], $clientID);
    $stepStepScriptPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsScript'], $clientID);

    $typesListID = getAppListID('stepsTypes');

    $relations = getRelations($roundID, $theSubjectID, $clientID);

    $finalResult = array();
    //Next, check that has one $relation
    if ((count($relations) - 1) == 0) {

        // build return properties array
        $returnProperties = array();
        $returnProperties[] = array('ID' => $stepMainPropertyID, 'name' => 'stepsMainValue');
        $returnProperties[] = array('ID' => $stepDescriptionPropertyID, 'name' => 'description');
        $returnProperties[] = array('ID' => $stepOrderPropertyID, 'name' => 'order');
        $returnProperties[] = array('ID' => $stepStepTypePropertyID, 'name' => 'stepType');
        $returnProperties[] = array('ID' => $stepStepScriptPropertyID, 'name' => 'stepScript');
        $returnProperties[] = array('ID' => $stepCheckedStepUnitsPropertyID, 'name' => 'checkedValues');

        //build the filter
        $filters = array();
        $filters[] = array('ID' => $stepParentTestCasePropertyID, 'value' => $theTC_ID);
        $filters[] = array('ID' => $stepRoundSubjectTestCasePropertyID, 'value' => $relations[0]['ID']);

        // get steps
        $orderedSteps = getFilteredItemsIDs($stepsItemTypeID, $clientID, $filters, $returnProperties, 'order');

        //Get the id of the list client related
        $clientListID = getClientListID_RelatedWith($typesListID, $clientID);

        //Get all the values for this list and client
        $ClientValues = getListValues($clientListID, $clientID);

        //Add the "isStep" parameter
        for ($i = 0; $i < count($orderedSteps); $i++) {

            //First, encode the name and the description
            $orderedSteps[$i]['stepsMainValue'] = base64_encode($orderedSteps[$i]['stepsMainValue']);
            $orderedSteps[$i]['description'] = base64_encode($orderedSteps[$i]['description']);

            //for every system list value, get the client list value

            for ($j = 0; $j < count($ClientValues); $j++) {
                if (strcmp($ClientValues[$j]['value'], $orderedSteps[$i]['stepType']) == 0) {
                    //get the client id value
                    $AppValueID = getAppListValueID_RelatedWith($ClientValues[$j]['valueID'], $clientID);

                    if ($AppValueID > 0) {
                        //Get the app value and add to client values
                        $orderedSteps[$i]['scriptAppValue'] = getAppValue($AppValueID);
                    } else {
                        //Not related. Add an empty value
                        $orderedSteps[$i]['scriptAppValue'] = 'undefined';
                    }
                    break;
                }
            }

            //Also, get their units and results
            // get params
            // get checked values
            $markedStepsUnitsIDs = explode(',', $orderedSteps[$i]['checkedValues']);

            $params = getParamsAndResultsForAStep($orderedSteps[$i], $studyID, $theSubjectID, $markedStepsUnitsIDs, $clientID);

            //Add every parameter to the results
            foreach ($params as $p) {

                //Check if the systemConversion value is a Selenium type

                if (base64_decode($p['systemConversionValue']) == 'stepUnit.type.seleniumResult') {
                    $orderedSteps[$i]['stepUnit_SELENIUMRESULT_ID'] = $p['ID'];
                    //Add the result id
                    $orderedSteps[$i]['stepUnit_SELENIUM_LASTRESULT_ID'] = $p['resultID'];
                    //Add the execution value
                    $orderedSteps[$i]['stepUnit_SELENIUMRESULT_EXECVALUE'] = $p['executionValue'];
                }
                if (base64_decode($p['systemConversionValue']) == 'stepUnit.type.seleniumResultDescription') {
                    $orderedSteps[$i]['stepUnit_SELENIUMRESULT_DESCRIPTION_ID'] = $p['ID'];
                    //Add the result id
                    $orderedSteps[$i]['stepUnit_SELENIUMDESCRIPTION_LASTRESULT_ID'] = $p['resultID'];

                }
            }
            $finalResult[] = $orderedSteps[$i];
        }

    }

    return $finalResult;
}

//Get all test cases inside a testCategory
function getTestCasesInsideACategory($testCategoryID, $theSubjectID, $roundID, $clientID) {
    global $definitions;

    $categoriesItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
    $categoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
    $testCasesArray = array();

    //First, get the internal structure of testCategories
    $tcatTree = getItemsTree($categoriesItemTypeID, $clientID, $categoryParentPropertyID, $testCategoryID);

    $idsToRetrieve = array();

    //Store all testCategories ids found in a plain array
    foreach ($tcatTree as $tc) {
        for ($j = 0; $j < count($tc); $j++) {
            if (!(in_array($tc[$j]['parent'], $idsToRetrieve))) {
                $idsToRetrieve[] = $tc[$j]['parent'];
            }
            if (!(in_array($tc[$j]['ID'], $idsToRetrieve))) {
                $idsToRetrieve[] = $tc[$j]['ID'];
            }
        }
    }

    //Get the relations item
    $relations = getRelations($roundID, $theSubjectID, $clientID);

    if ((count($relations) - 1) == 0) {
        //Get the test cases ids inside an array
        $idsToSearch = explode(',', $relations[0]['testCasesIDs']);
        for ($i = 0; $i < count($idsToRetrieve); $i++) {
            //Get the test cases and filter
            $availableTestCases = array();
            $availableTestCases = getFilteredTestCasesInsideCategory($idsToRetrieve[$i], $idsToSearch, $clientID);
            //And add to results
            for ($j = 0; $j < count($availableTestCases); $j++) {
                $partRes = array();
                $partRes['ID'] = $availableTestCases[$j]['ID'];
                $testCasesArray[] = $partRes;
            }
        }
    }

    return $testCasesArray;
}

//Get and fill the test case
function getTestCaseData($testCaseID, $theSubjectID, $roundID, $studyID, $clientID) {
    global $definitions;

    $testCaseParentTestCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);

    $theTestCase = array();
    $theTestCase['TC_ID'] = $testCaseID;
    $theTestCase['ROUND_ID'] = $roundID;
    $theTestCase['STUDY_ID'] = $studyID;
    $theTestCase['SUBJECT_ID'] = $theSubjectID;

    //Also, get the parent testCategory for the test case
    $theTestCase['PARENT_TC_ID'] = getItemPropertyValue($testCaseID, $testCaseParentTestCategoryPropertyID, $clientID);

    //Get the steps for this testCase
    $theSteps = array();

    $theSteps = getStepsScriptsForTestCase($testCaseID, $theSubjectID, $roundID, $studyID, $clientID);
    //print("Steps scritps details for testCase $testCaseID and subject $theSubjectID\n\r" );
    //print_r($theSteps);
    $stepsCount = 0;
    //Only returns the testcase if any step of the test case is automated and has the type passed (TODO this last)
    $hasAutomatedSteps = "NO";

    //Add the steps to the result
    for ($i = 0; $i < count($theSteps); $i++) {
        if ($theSteps[$i]['scriptAppValue'] == 'steps.types.php') {
            //print("found automated step\n\r");
            $theTestCase['STEP_ID_' . $stepsCount] = $theSteps[$i]['ID'];
            $theTestCase['STEP_TYPE_' . $stepsCount] = $theSteps[$i]['stepType'];
            $theTestCase['STEP_APPTYPE_' . $stepsCount] = $theSteps[$i]['scriptAppValue'];
            $theTestCase['STEP_RESULT_ID_' . $stepsCount] = $theSteps[$i]['stepUnit_Result_ID'];
            //Get the stepUnits values

            if (isset($theSteps[$i]['stepUnit_SELENIUMRESULT_ID'])) {
                $theTestCase['STEP_STEPUNITS_SELENIUMRESULT_ID_' . $stepsCount] = $theSteps[$i]['stepUnit_SELENIUMRESULT_ID'];
            }
            if (isset($theSteps[$i]['stepUnit_SELENIUMRESULT_DESCRIPTION_ID'])) {
                $theTestCase['STEP_STEPUNITS_SELENIUMRESULT_DESCRIPTION_ID_' . $stepsCount] = $theSteps[$i]['stepUnit_SELENIUMRESULT_DESCRIPTION_ID'];
            }
            if (isset($theSteps[$i]['stepUnit_SELENIUM_LASTRESULT_ID'])) {
                $theTestCase['STEP_STEPUNITS_SELENIUMRESULT_LASTRESULT_ID_' . $stepsCount] = $theSteps[$i]['stepUnit_SELENIUM_LASTRESULT_ID'];

            }
            if (isset($theSteps[$i]['stepUnit_SELENIUMDESCRIPTION_LASTRESULT_ID'])) {
                $theTestCase['STEP_STEPUNITS_SELENIUMRESULTDESCRIPTION_LASTRESULT_ID_' . $stepsCount] = $theSteps[$i]['stepUnit_SELENIUMDESCRIPTION_LASTRESULT_ID'];

            }
            if (isset($theSteps[$i]['stepUnit_SELENIUMRESULT_EXECVALUE'])) {
                $theTestCase['STEP_STEPUNITS_SELENIUMRESULTEXECVALUE_' . $stepsCount] = $theSteps[$i]['stepUnit_SELENIUMRESULT_EXECVALUE'];

            }

            $stepsCount++;
            $hasAutomatedSteps = "YES";
        }
    }

    if ($hasAutomatedSteps == "YES") {
        //And return the test case
        //print ("Return automated testCase: $testCaseID\n\r");
        return $theTestCase;
    } else {
        //print ("Not automated testCase $testCaseID. Return null\n\r");
        return null;
    }

}

//Updates a automatedResultsFor a testCase
function updateAutomatedResultForATestCase($testCaseID, $subjectID, $roundID, $clientID) {
    global $definitions;

    //DEFINITIONS FOR ITEM TYPES
    $resultsRelationsID = getClientItemTypeID_RelatedWith_byName($definitions['automatizationResultsRelations'], $clientID);

    //DEFINITIONS FOR PROPERTIES
    $parentTestCategoryPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);
    $parentStudyForSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subjectStudyID'], $clientID);
    $autResRelRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsRoundID'], $clientID);
    $autResRelSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsSubjectID'], $clientID);
    $autResRelParentCatPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsTestCategoryParentID'], $clientID);
    $autResRelCatPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsTestCategoryID'], $clientID);
    $autResRelTestCasesCountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsTestCasesCount'], $clientID);
    $autResRelTestCasesOKPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsTestCasesOKCount'], $clientID);
    $autResRelTestCasesNOKPropertyID = getClientPropertyID_RelatedWith_byName($definitions['automatizationResultsRelationsTestCasesNOKCount'], $clientID);
    $categoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);

    //Next, get the parentID value
    $parentFolderID = getItemPropertyValue($testCaseID, $parentTestCategoryPropertyID, $clientID);

    //Get the studyID value for this subject
    $studyID = getItemPropertyValue($subjectID, $parentStudyForSubjectPropertyID, $clientID);

    //We need also, the related items
    $relations = getRelations($roundID, $subjectID, $clientID);

    //When we've the relations, we need count the steps for every test
    if (count($relations) - 1 == 0) {
        $theTestCases = explode(",", $relations[0]['testCasesIDs']);

        $totalTc = 0;
        $totalOK = 0;
        $totalNOK = 0;

        //Get directly testcases that are related
        $relatedTestCases = getFilteredTestCasesInsideCategory($parentFolderID, $theTestCases, $clientID);

        foreach ($relatedTestCases as $tc) {
            $detailedTc = getTestCaseData($tc['ID'], $subjectID, $roundID, $studyID, $clientID);

            //print("DetailedTC for tc ".$tc['ID']." in testCategory $testCategory \n\r");
            //print_r($detailedTc);

            if ($detailedTc != null) {
                //print ("process automated testcase ".$tc['ID'].":\n\r" );
                //print("TestCase Data for testCase ".$tc['ID'].":\n\r");
                //print_r($detailedTc);

                $totalTc++;
                $counter = 0;
                $counterOK = 0;
                $counterNOK = 0;

                //Search their steps results for Selenium results
                while (isset($detailedTc['STEP_ID_' . $counter])) {

                    if (isset($detailedTc['STEP_STEPUNITS_SELENIUMRESULTEXECVALUE_' . $counter])) {
                        if (base64_decode($detailedTc['STEP_STEPUNITS_SELENIUMRESULTEXECVALUE_' . $counter]) == 'OK') {
                            $counterOK++;

                        } elseif (base64_decode($detailedTc['STEP_STEPUNITS_SELENIUMRESULTEXECVALUE_' . $counter]) == 'NOK') {
                            $counterNOK++;

                        }
                    }
                    $counter++;
                }//End while

                //Check if the final state for the tc is ok, nok or not executed
                if ($counterNOK > 0) {
                    //Any step nok. Final result NOK
                    $totalNOK++;
                } else {
                    if ($counterOK > 0) {
                        //NOK = 0, any tc OK. Final result OK
                        $totalOK++;
                    } else {
                        //all values 0.Do nothing
                    }
                }
            } //End if detailed TC

        }//End foreach related test cases

        //Finally, check if relation results exists for this relation, round and testCategory
        if ($totalTc > 0) {
            // build return properties array
            $returnProperties = array();

            //build the filter
            $filters = array();
            $filters[] = array('ID' => $autResRelRoundPropertyID, 'value' => $roundID);
            $filters[] = array('ID' => $autResRelSubjectPropertyID, 'value' => $subjectID);
            $filters[] = array('ID' => $autResRelCatPropertyID, 'value' => $parentFolderID);

            // get relations
            $resultRelation = getFilteredItemsIDs($resultsRelationsID, $clientID, $filters, $returnProperties);

            if (count($resultRelation) - 1 == 0) {
                //exists
                //Update the values
                //print ("updating total tc for itemRelation: TestCategory: $testCategory, RoundID: $roundID, SubjectID: $subject, totalTC value:$totalTc \n\r");
                //flush();
                setPropertyValueByID($autResRelTestCasesCountPropertyID, $resultsRelationsID, $resultRelation[0]['ID'], $clientID, $totalTc);

                //print ("updating tc OK for itemRelation: TestCategory: $testCategory, RoundID: $roundID, SubjectID: $subject, testCases OK value:$totalOK \n\r");
                //flush();
                setPropertyValueByID($autResRelTestCasesOKPropertyID, $resultsRelationsID, $resultRelation[0]['ID'], $clientID, $totalOK);

                //print ("updating tc NOK for itemRelation: TestCategory: $testCategory, RoundID: $roundID, SubjectID: $subject, testCases NOK value:$totalNOK \n\r");
                //flush();
                setPropertyValueByID($autResRelTestCasesNOKPropertyID, $resultsRelationsID, $resultRelation[0]['ID'], $clientID, $totalNOK);

            } else {
                if (count($resultRelation) - 1 > 0) {
                    //error. More than 1 relation
                    print("Error. Found more than one automated results relation:\n");
                    print_r($resultRelation);
                    flush();
                    exit ;
                } else {
                    //does not exists
                    //Create a new relation
                    //First, get the parent TCategory

                    $parentCategoryID = getItemPropertyValue($parentFolderID, $categoryParentPropertyID, $clientID);
                    //print("Parent category id for testCategory $testCategory = $parentCategoryID \n\r");
                    //flush();
                    $propertiesValues = array( array('ID' => $autResRelRoundPropertyID, 'value' => $roundID), array('ID' => $autResRelSubjectPropertyID, 'value' => $subjectID), array('ID' => $autResRelCatPropertyID, 'value' => $parentFolderID), array('ID' => $autResRelParentCatPropertyID, 'value' => $parentCategoryID), array('ID' => $autResRelTestCasesCountPropertyID, 'value' => $totalTc), array('ID' => $autResRelTestCasesOKPropertyID, 'value' => $totalOK), array('ID' => $autResRelTestCasesNOKPropertyID, 'value' => $totalNOK));

                }//end if count resultRelation>0

            }//end if count resultRelation==0
        }//End if totalTC>0

    }//End if count relations

}

//Returns OK,NOK,NOT_EXECUTED or NO_SELENIUM for a test cases in a relation subject-round
function getSeleniumResultsForTestCase($testCaseID, $relationID, $studyID, $subjectID, $clientID) {

    global $definitions;
    $stepsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);

    $stepParentTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
    $stepOrderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsOrder'], $clientID);
    $stepCheckedStepUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);
    $stepRoundSubjectTestCasePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsRoundSubjectRelationID'], $clientID);

    //First, we need the steps for this testCase

    //Next, get the results fo the step
    // get checked values
    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $stepOrderPropertyID, 'name' => 'order');
    $returnProperties[] = array('ID' => $stepCheckedStepUnitsPropertyID, 'name' => 'checkedValues');

    //build the filter
    $filters = array();
    $filters[] = array('ID' => $stepParentTestCasePropertyID, 'value' => $testCaseID);
    $filters[] = array('ID' => $stepRoundSubjectTestCasePropertyID, 'value' => $relationID);

    // get steps
    $orderedSteps = getFilteredItemsIDs($stepsItemTypeID, $clientID, $filters, $returnProperties, 'order');

    $totalOK = 0;
    $totalNOK = 0;
    $hasSeleniumResults = False;

    for ($i = 0; $i < count($orderedSteps); $i++) {

        $markedStepsUnitsIDs = explode(',', $orderedSteps[$i]['checkedValues']);

        $params = getParamsAndResultsForAStep($orderedSteps[$i], $studyID, $subjectID, $markedStepsUnitsIDs, $clientID);

        //Add every parameter to the results
        foreach ($params as $p) {

            //Check if the systemConversion value is a Selenium type

            if (base64_decode($p['systemConversionValue']) == 'stepUnit.type.seleniumResult') {
                $hasSeleniumResults = True;
                $theResult = base64_decode($p['executionValue']);
                if ($theResult == 'OK') {
                    $totalOK++;
                } elseif ($theResult == 'NOK') {
                    $totalNOK++;
                }
            }

        }
    }
    if ($hasSeleniumResults == True) {
        if ($totalOK > 0 && $totalNOK == 0) {
            return 'OK';
        } elseif ($totalNOK > 0) {
            return 'NOK';
        } elseif ($totalOK == 0 && $totalNOK == 0) {
            return 'NOT_EXECUTED';
        }
    } else {
        return 'NOSELENIUM';
    }

}

//Get the relations properties
function getRelations($theRoundID, $theSubjectID, $clientID) {
    global $definitions;

    $relationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

    $relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
    $relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
    $relationsTestCasesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
    $relationsTestCategoriesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);

    $relationsTestCategoriesPropertyID;

    //First get the relation associated
    $returnProperties = array();
    $returnProperties[] = array('ID' => $relationsRoundPropertyID, 'name' => 'roundID');
    $returnProperties[] = array('ID' => $relationsSubjectPropertyID, 'name' => 'subjectID');
    $returnProperties[] = array('ID' => $relationsTestCasesPropertyID, 'name' => 'testCasesIDs');
    $returnProperties[] = array('ID' => $relationsTestCategoriesPropertyID, 'name' => 'testCatIDs');

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $relationsRoundPropertyID, 'value' => $theRoundID);
    $filters[] = array('ID' => $relationsSubjectPropertyID, 'value' => $theSubjectID);

    $relations = getFilteredItemsIDs($relationsItemTypeID, $clientID, $filters, $returnProperties);

    return $relations;
}

//Get all the subjects for the study
function getSubjectsForStudy($theStudyID, $clientID) {
    global $definitions;

    $subjectItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subject'], $clientID);
    $subjectStudy_PropID = getClientPropertyID_RelatedWith_byName($definitions['subjectStudyID'], $clientID);

    //First get the relation associated
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $subjectStudy_PropID, 'value' => $theStudyID);

    $subjects = getFilteredItemsIDs($subjectItemTypeID, $clientID, $filters, $returnProperties);

    return $subjects;

}

//Get all the rounds for the study
function getRoundsForStudy($theStudyID, $clientID) {
    global $definitions;

    $roundItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);
    $roundsAssociatedStudyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedStudyID'], $clientID);

    //First get the relation associated
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $roundsAssociatedStudyID, 'value' => $theStudyID);

    $rounds = getFilteredItemsIDs($roundItemTypeID, $clientID, $filters, $returnProperties);

    return $rounds;

}

//Get all the groups for the study
function getGroupsForStudy($theStudyID, $clientID) {
    global $definitions;

    $groupsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);
    $groupsAssociatedStudyID = getClientPropertyID_RelatedWith_byName($definitions['groupsStudyID'], $clientID);

    //First get the relation associated
    $returnProperties = array();

    //build an empty filter
    $filters = array();
    $filters[] = array('ID' => $groupsAssociatedStudyID, 'value' => $theStudyID);

    $groups = getFilteredItemsIDs($groupsItemTypeID, $clientID, $filters, $returnProperties);

    return $groups;

}
?>
