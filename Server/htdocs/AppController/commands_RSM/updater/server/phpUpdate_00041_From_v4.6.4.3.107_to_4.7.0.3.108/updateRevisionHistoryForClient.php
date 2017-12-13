<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_revision_history($clientsToUpdate){
	
	global $definitions;
	
	if(count($clientsToUpdate)==0){
		print ("No specific clients defined, processing all clients: �\n");
		// get all clients
		$theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");
		
		while($client=$theQuery->fetch_assoc()) {
			$clientsToUpdate[]=$client['RS_ID'];
		}
	}
	
	print ("Selected clients to update: �\n");
	print_r($clientsToUpdate);
	
	//Update the defined clients
	for ($cliNum=0;$cliNum<count($clientsToUpdate);$cliNum++){
		
		//Get the clientID
		$clientID = $clientsToUpdate[$cliNum];
		
		print ("Starting process for client ".$clientID."�\n");
		
		// get revision item type
		$revisionItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['revisionHistory'], $clientID);
		$revisionProperties = getClientItemTypeProperties($revisionItemTypeID, $clientID);
		
		$buildItemTypeID    = getClientItemTypeID_RelatedWith_byName($definitions['productBuild'], $clientID);
		$buildProperties    = getClientItemTypeProperties($buildItemTypeID, $clientID);
		$studyItemTypeID    = getClientItemTypeID_RelatedWith_byName($definitions['studies'], $clientID);
		
		foreach($revisionProperties as $property){
			if($property["type"]=="identifier"){
				if($buildItemTypeID==getClientPropertyReferredItemType($property["id"], $clientID)){
					$versionPropertyID=$property["id"];
				}
			}elseif($property["name"]=="Affected modules"){
				$affectedPropertyID=$property["id"];
			}elseif($property["name"]=="Revision"){
				$revisionPropertyID=$property["id"];
			}elseif($property["name"]=="ES"){
				$esPropertyID=$property["id"];
			}elseif($property["name"]=="EN"){
				$enPropertyID=$property["id"];
			}elseif($property["name"]=="DE"){
				$dePropertyID=$property["id"];
			}
		}
		foreach($buildProperties as $property){
			if($property["type"]=="identifier"){
				if($studyItemTypeID==getClientPropertyReferredItemType($property["id"], $clientID)){
					$productPropertyID=$property["id"];
				}
			}
		}
		
		if($versionPropertyID!=0){
			//update property
			$theQuery = 'UPDATE rs_item_properties SET RS_REFERRED_ITEMTYPE="0" WHERE RS_PROPERTY_ID='.$versionPropertyID.' AND RS_CLIENT_ID='.$clientID;
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$versionPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryVersion'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		if($affectedPropertyID!=0){
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$affectedPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryAffectedModules'])."', NOW())";
			$result = RSquery($theQuery);
		}

		if($revisionPropertyID!=0){
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$revisionPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryRevision'])."', NOW())";
			$result = RSquery($theQuery);
		}
				
		if($esPropertyID!=0){
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$esPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryDescriptionES'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		if($enPropertyID!=0){
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$enPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryDescriptionEN'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		if($dePropertyID!=0){
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$dePropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['revisionHistoryDescriptionDE'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		if($productPropertyID!=0){
			//update property
			$theQuery = 'UPDATE rs_item_properties SET RS_REFERRED_ITEMTYPE="0" WHERE RS_PROPERTY_ID='.$productPropertyID.' AND RS_CLIENT_ID='.$clientID;
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$productPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['productBuildProduct'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		
		
		print ("Client with ID".$clientID."Finished!�\n");
	}

	//RETURN OK RESULT
	return "OK";

}

?>