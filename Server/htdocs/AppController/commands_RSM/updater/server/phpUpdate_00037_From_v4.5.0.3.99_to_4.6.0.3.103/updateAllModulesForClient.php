<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_modules($clientsToUpdate){
	
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
		
		print ("Starting process for client".$clientID."�\n");
		//create itemtype
		$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
		$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",0,'".$clientID."', 'configurationModuleGeneric', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
		$result = RSquery($theQuery);
		//create category
		$categoryID=getNextIdentification('rs_categories','RS_CATEGORY_ID',$clientID);
		$theQuery = "INSERT INTO rs_categories (RS_CATEGORY_ID, RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_NAME, RS_ORDER) VALUES (".$categoryID.",'".$clientID."','".$itemTypeID."', 'main', ".getGenericNext('rs_categories','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
		$result = RSquery($theQuery);
		//create properties
		$namePropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
		$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$namePropertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
		$result = RSquery($theQuery);
		
		$descriptionPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
		$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$descriptionPropertyID.','.$categoryID.','.$clientID.',"description","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
		$result = RSquery($theQuery);
		
		$logoPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
		$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$logoPropertyID.','.$categoryID.','.$clientID.',"logo","image","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
		$result = RSquery($theQuery);
		//relate itemtype
		createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['configurationModuleGeneric']),$clientID);
		//relate properties
		$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$namePropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationModuleGenericName'])."', NOW())";
		$result = RSquery($theQuery);
		$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$descriptionPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationModuleGenericDescription'])."', NOW())";
		$result = RSquery($theQuery);
		$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$logoPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationModuleGenericLogo'])."', NOW())";
		$result = RSquery($theQuery);
		
		//get all client_actions
		$theQuery = 'SELECT * FROM rs_actions_clients WHERE RS_CLIENT_ID = '.$clientID;
		$results = RSquery($theQuery);
		
		while($row=$results->fetch_assoc()){
			//get new id
			$clientActionID=getNextIdentification('rs_actions_clients','RS_ID',$clientID);
			
			//create item and properties
			$propertiesValues=array();
			$propertiesValues[]=array('ID'=>$namePropertyID,'value'=>$row['RS_MODULE_NAME']);
			$propertiesValues[]=array('ID'=>$descriptionPropertyID,'value'=>$row['RS_MODULE_DESCRIPTION']);
			$propertiesValues[]=array('ID'=>$logoPropertyID,'value'=>$row['RS_MODULE_LOGO']);
			$configurationItemID=createItem($itemTypeID,$clientID,$propertiesValues);
			
			//update id and itemID
			$theQuery = 'UPDATE rs_actions_clients SET RS_ID = '.$clientActionID.', RS_CONFIGURATION_ITEM_ID = '.$configurationItemID.' WHERE RS_CLIENT_ID = '.$clientID.' AND RS_ACTION_ID = '.$row['RS_ACTION_ID'];
			$result = RSquery($theQuery);
			
			//update all action groups with new id
			$theQuery = 'UPDATE rs_actions_groups SET RS_ACTION_CLIENT_ID = '.$clientActionID.' WHERE RS_CLIENT_ID = '.$clientID.' AND RS_ACTION_ID = '.$row['RS_ACTION_ID'];
			$result = RSquery($theQuery);
		}
		
			
		print ("Client with ID".$clientID."Finished!�\n");
	}

	//RETURN OK RESULT
	return "OK";

}
?>