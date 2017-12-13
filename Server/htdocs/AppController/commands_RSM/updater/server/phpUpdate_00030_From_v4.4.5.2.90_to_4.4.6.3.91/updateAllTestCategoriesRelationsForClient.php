<?php
//***************************************************
//Description:
//	 Update all relations. Add testCategories for the passed client
//***************************************************

// Database connection startup
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_relations($clientsToUpdate){
global $definitions;
	
	if(count($clientsToUpdate)==0){
		print ("No specific clients defined, processing all clients: �_n");
		// get all clients
		$theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");
		
		while($client=$theQuery->fetch_assoc()) {
			$clientsToUpdate[]=$client['RS_ID'];
		}
	}
	
	print ("Selected clients to update: �_n");
	print_r($clientsToUpdate);
	
	//Update the defined clients
	for ($cliNum=0;$cliNum<count($clientsToUpdate);$cliNum++){
		
		//Get the clientID
		$clientID = $clientsToUpdate[$cliNum];
		
		print ("Starting process for client".$clientID."�_n");
		
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
		$testCategoryParentGroupID = getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryGroupID'],$clientID);
		$tcParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsTestCaseParentID'], $clientID);
		
	
		//First, we need get all relations for this client
		$relationsIDs = getItemIDs($itemTypeRelationsID,$clientID);

		//Next foreach relation, get and update the testCategories relations
		
		foreach ($relationsIDs as $relID){
				print ("--------------------------�_n");
				print ("Starting process for relation ID:".$relID."�_n");
								//Get the testCases relation property
				$relTestCases = getItemPropertyValue($relID, $relationsTestCasesPropertyID, $clientID);
				
				//Get the actual testCategories relation property
				$relTestCategories = getItemPropertyValue($relID, $relationsTestCategoriesPropertyID, $clientID);
			
				
				//Split the values and search for every test case
				$toSearch = explode(",",$relTestCases);
								
				for ($i=0;$i<count($toSearch);$i++){
					//Get the parent test categories for this testcase
					$parentTCInversedList = getParentCategoriesForTestCase($toSearch[$i],$clientID, $itemTypeTestCasesCategoriesID,$testCategoryParentGroupID,$testCasesParentPropertyID,$itemTypeTestCasesID,$testCategoryParentPropertyID);
			
					
					$relTestCategories = addTestCategoriesToRelationIfNotExists($relTestCategories, $parentTCInversedList);
					
				}
				
				print ("Final testCategories to update: �_n");
				print ($relTestCategories."�_n");
				//Finally, update the relations for this item
				setPropertyValueByID($relationsTestCategoriesPropertyID, $itemTypeRelationsID, $relID, $clientID, $relTestCategories, '', $RSuserID);
				print "--------------------------�_n";

		}
		
		print ("Client with ID".$clientID."Finished!�_n");

	}

//RETURN OK RESULT
return "OK";

}

//Adds the testCategories that aren't in the relation
function addTestCategoriesToRelationIfNotExists($testCategoriesList, $listToAdd)
{
	//First, split the two lists
	$existingRelation = explode(',',$testCategoriesList);
	$toAddIds = explode(',',$listToAdd);
	
	//Only add if previously not exists
	for($i=0;$i<count($toAddIds);$i++){
		$theAddedID = $toAddIds[$i];
		if (!in_array($theAddedID, $existingRelation))
		{
			//The item does not exist
			$existingRelation[]=$theAddedID;
		}
	
	}
	
	//Finally, implode the list of categories and return
	//First, clear empty values
	$existingRelation = array_filter($existingRelation);
	return implode(',',$existingRelation);

}

//Get the structure from the testCase to the first test category. Returns all inversed testCategories tree
function getParentCategoriesForTestCase($testCaseID,$clientID, $itemTypeTestCasesCategoriesID,$testCategoryParentGroupID,$testCasesParentPropertyID,$itemTypeTestCasesID,$testCategoryParentPropertyID){
	
	//First, get the parent test category for the test case
	
	$categoriesArray = "";

	$aux = getItemPropertyValue($testCaseID, $testCasesParentPropertyID, $clientID);
	
	$categoriesArray = $categoriesArray.$aux;
		
	//This is the first category directly associated to the testcase. We need search the tree categories (inversed)
	while ($aux!=0){
		$aux = getItemPropertyValue($aux, $testCategoryParentPropertyID, $clientID);
		if ($aux!=0){
			$categoriesArray = $categoriesArray.",".$aux;
		}

	}	
	return $categoriesArray;
}

?>