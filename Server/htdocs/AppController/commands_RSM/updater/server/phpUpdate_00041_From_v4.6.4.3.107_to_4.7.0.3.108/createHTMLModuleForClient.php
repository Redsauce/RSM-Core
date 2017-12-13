<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_create_HTML_modules($clientsToUpdate){
	
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
		
		//check configuration itemtype exists
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['configurationHTMLModule'], $clientID);
		if($itemTypeID==0){
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",0,'".$clientID."', 'Configuration for HTML Module', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['configurationHTMLModule']),$clientID);
		}
		//check category exists
		$categories=getClientItemTypeCategories($itemTypeID, $clientID);
		if(count($categories)>0){
			$categoryID=$categories[0]['id'];
		}else{
			//create category
			$categoryID=getNextIdentification('rs_categories','RS_CATEGORY_ID',$clientID);
			$theQuery = "INSERT INTO rs_categories (RS_CATEGORY_ID, RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_NAME, RS_ORDER) VALUES (".$categoryID.",'".$clientID."','".$itemTypeID."', 'main', ".getGenericNext('rs_categories','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
		}
		
		$namePropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleName'], $clientID);
		if($namePropertyID==0){
			//create property
			$namePropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$namePropertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$namePropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleName'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleDescription'], $clientID);
		if($descriptionPropertyID==0){
			//create property
			$descriptionPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$descriptionPropertyID.','.$categoryID.','.$clientID.',"description","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$descriptionPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleDescription'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$logoPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleLogo'], $clientID);
		if($logoPropertyID==0){
			//create property
			$logoPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$logoPropertyID.','.$categoryID.','.$clientID.',"logo","image","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$logoPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleLogo'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$URLPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleURL'], $clientID);
		if($URLPropertyID==0){
			//create property
			$URLPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$URLPropertyID.','.$categoryID.','.$clientID.',"URL","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$URLPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleURL'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$methodPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleMethod'], $clientID);
		if($methodPropertyID==0){
			//create property
			$methodPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$methodPropertyID.','.$categoryID.','.$clientID.',"method","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$methodPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleMethod'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$requestVarsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationHTMLModuleRequestVars'], $clientID);
		if($requestVarsPropertyID==0){
			//create property
			$requestVarsPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$requestVarsPropertyID.','.$categoryID.','.$clientID.',"request vars","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$requestVarsPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationHTMLModuleRequestVars'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		//create item and properties
		$propertiesValues=array();
		$propertiesValues[]=array('ID'=>$namePropertyID,'value'=>"HTML viewer");
		$propertiesValues[]=array('ID'=>$descriptionPropertyID,'value'=>"View HTML page contents inside RSM");
		$propertiesValues[]=array('ID'=>$logoPropertyID,'value'=>"");
		$propertiesValues[]=array('ID'=>$URLPropertyID,'value'=>"http://redsauce.net");
		$propertiesValues[]=array('ID'=>$methodPropertyID,'value'=>"GET");
		$propertiesValues[]=array('ID'=>$requestVarsPropertyID,'value'=>"");
		$configurationItemID=createItem($itemTypeID,$clientID,$propertiesValues);
		
		//add action_client
		$theQuery = "INSERT INTO rs_actions_clients (RS_ID, RS_CONFIGURATION_ITEM_ID, RS_CLIENT_ID, RS_ACTION_ID) VALUES (".getNextIdentification('rs_actions_clients', 'RS_ID', $clientID).", ".$configurationItemID.", ".$clientID.", (SELECT RS_ID FROM rs_actions WHERE RS_NAME='rsm.mainpanel.HTML.access'))";
		$result = RSquery($theQuery);
		
		print ("Client with ID".$clientID."Finished!�\n");
	}

	//RETURN OK RESULT
	return "OK";

}

?>