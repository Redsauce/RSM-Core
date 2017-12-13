<?php
//***************************************************
//Description:
//	 Update all relations. Add testCategories for the passed client
//***************************************************

// Database connection startup
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../../utilities/RSMtestsManagement.php";

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_relations($clientsToUpdate) {
    global $definitions;

    if (count($clientsToUpdate) == 0) {
        print("No specific clients defined, processing all clients: �_n");
        // get all clients
        $theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");

        while ($client = $theQuery->fetch_assoc()) {
            $clientsToUpdate[] = $client['RS_ID'];
        }
    }

    print("Selected clients to update: �_n");
    print_r($clientsToUpdate);

    //Update the defined clients
    for ($cliNum = 0; $cliNum < count($clientsToUpdate); $cliNum++) {

        //Get the clientID
        $clientID = $clientsToUpdate[$cliNum];

        print("Starting process for client" . $clientID . "�_n");

        // get item types
        $itemTypeTestCasesCategoriesID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
        $itemTypeTestCasesID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
        $itemTypeRelationsID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);

        // get properties
        $relationsRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
        $relationsSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);
        $relationsTestCasesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestID'], $clientID);
        $relationsTestCategoriesPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsTestCatIDs'], $clientID);
        $testCategoryParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);
        $testCasesParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesFolderID'], $clientID);
        $testCategoryParentGroupID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'], $clientID);
        $tcParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);

        //First, we need get all relations for this client
        $relationsIDs = getItemIDs($itemTypeRelationsID, $clientID);

        //Next foreach relation, get and update the testCategories relations

        foreach ($relationsIDs as $relID) {
            print("--------------------------�_n");
            print("Starting process for relation ID:" . $relID . "�_n");
            //Get the testCases relation property
            $relTestCases = getItemPropertyValue($relID, $relationsTestCasesPropertyID, $clientID);

            //Get the actual testCategories relation property
            $relTestCategories = getItemPropertyValue($relID, $relationsTestCategoriesPropertyID, $clientID);

            //Get the subjectID relation property
            $subjectID = getItemPropertyValue($relID, getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID), $clientID);

            //Get the roundID relation property
            $roundID = getItemPropertyValue($relID, getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID), $clientID);

            //Split the values and search for every test case
            $toSearch = explode(",", $relTestCases);

            for ($i = 0; $i < count($toSearch); $i++) {
                //Get the parent test categories for this testcase
                //$parentTCInversedList = getParentCategoriesForTestCase($toSearch[$i],$clientID, $itemTypeTestCasesCategoriesID,$testCategoryParentGroupID,$testCasesParentPropertyID,$itemTypeTestCasesID,$testCategoryParentPropertyID);
                $parentTCInversedList = getParentCategoriesForTestCase($toSearch[$i], $clientID);

                //$relTestCategories = addTestCategoriesToRelationIfNotExists($relTestCategories, $parentTCInversedList);

                //Update the items TestCategories with the ids that aren't in the relation
                $relTestCategories = addItemsToRelationIfNotExists($relTestCategories, implode(',', $parentTCInversedList));

                //launch the updating process
                updateAutomatedResultForATestCategory($parentTCInversedList[0], $subjectID, $roundID, $clientID);

            }

            print("Final testCategories to update: �_n");
            print($relTestCategories . "�_n");
            //Finally, update the relations for this item
            setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relID, $clientID, $relTestCategories, '', $RSuserID);
            print "--------------------------�_n";

        }

        print("Client with ID" . $clientID . "Finished!�_n");

    }

    //RETURN OK RESULT
    return "OK";

}
?>