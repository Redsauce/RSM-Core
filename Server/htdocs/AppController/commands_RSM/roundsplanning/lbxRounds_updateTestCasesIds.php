<?php
//***************************************************
//Description:
//	 Update a round associated test cases 
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$roundID   = $GLOBALS['RS_POST']['roundID'];
$mode = $GLOBALS['RS_POST']['mode'];
$parentTCID =$GLOBALS['RS_POST']['parentTCID'];

$roundAssociatedTCIDsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedTestCasesIDs'], $clientID);
$itemTypeRoundID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);

$itemTypeCategoryID = getClientItemTypeID_RelatedWith_byName($definitions['testcasescategory'], $clientID);
$itemParentPropertyID =getClientPropertyID_RelatedWith_byName($definitions['testcasescategoryParentID'], $clientID);

//First, get the actual associated test categories
$theRoundTCIds = getItemPropertyValue($roundID, $roundAssociatedTCIDsPropertyID, $clientID);

//Get all childs associated to the TC
$auxResult = array();
$auxResult = getItemsTree($itemTypeCategoryID, $clientID, $itemParentPropertyID, $parentTCID);

//Get Only the ids
$tcTree = array();
$tempArray = array();
$tempArray['ID'] = $parentTCID;

array_push($tcTree,$tempArray);

if ($auxResult != null)
	foreach ($auxResult as $axr)
		foreach ($axr as $tc){
			$tempArray['ID'] = $tc['ID'];
			array_push($tcTree,$tempArray);	
		}	
	
$TCIdsArray = array();
$TCIdsArray = explode(',',$theRoundTCIds);

//Select by mode
switch ($mode){
	case 'uncheck':
		//All childs do will cleared
		//Find in the tc and remove from the round tc list
		for ($i=0;$i<count($tcTree); $i++){
			$keyPosition=-1;
			$keyPosition = array_search($tcTree[$i]['ID'],$TCIdsArray);
			if ($keyPosition>-1){
				//Key found. Delete it.
				unset($TCIdsArray[$keyPosition]);
			}
			

		}  
		break;
		
	case 'check':
		//All childs do will marked
		//First find if the child exists
		for ($i=0;$i<count($tcTree); $i++){
			$keyPosition = array_search($tcTree[$i]['ID'],$TCIdsArray);
			
			if (!$keyPosition){
				
				//Key not found. add it.
				
				$TCIdsArray[]=$tcTree[$i]['ID'];
				//print "añadido valor: ".$tcTree[$i]['ID']."\n";
				//print "resultado parcial: \n";
				//print_r($TCIdsArray);
				
			}
			

		}  

		break;	
}

//Finally, recreate the array and update the ids in the round
$TCIdsArray = array_values($TCIdsArray);

//print ("Array despues de añadir o quitar items:\n");
//print_r($TCIdsArray);
//exit;

$finalAssociatedValues = '';
for ($i=0;$i<count($TCIdsArray);$i++){
	$finalAssociatedValues = $finalAssociatedValues.$TCIdsArray[$i];
	if ($i<count($TCIdsArray)-1) $finalAssociatedValues = $finalAssociatedValues.',';	
}

//print "Resultado final a insertar:\n";
//print $finalAssociatedValues;
//exit;
//If the first or last value is a comma, remove it
$finalAssociatedValues = trim($finalAssociatedValues,",");

setPropertyValueByID($roundAssociatedTCIDsPropertyID,$itemTypeRoundID, $roundID, $clientID, $finalAssociatedValues, '', $RSuserID);

$results['newAssociatedTCIds'] = $finalAssociatedValues;

RSReturnArrayResults($results);
?>

