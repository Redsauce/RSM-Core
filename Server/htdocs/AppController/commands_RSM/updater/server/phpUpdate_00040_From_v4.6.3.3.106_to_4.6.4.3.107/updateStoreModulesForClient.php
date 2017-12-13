<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_store_modules($clientsToUpdate){
	
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
		
		//check itemtypes exist
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['onlineStore'], $clientID);
		$propertyID=0;
		if($itemTypeID==0){
			//get main property ID
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",".$propertyID.",'".$clientID."', 'onlineStore', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['onlineStore']),$clientID);
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
		if($propertyID!=0){
			//create main property
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreURL'], $clientID);
		if($propertyID==0){
			//create URL property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"URL","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreURL'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['onlineStoreCategory'], $clientID);
		$propertyID=0;
		if($itemTypeID==0){
			//get main property ID
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",".$propertyID.",'".$clientID."', 'onlineStoreCategory', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['onlineStoreCategory']),$clientID);
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
		if($propertyID!=0){
			//create main property
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreCategoryStoreID'], $clientID);
		if($propertyID==0){
			//create storeID property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"Store","identifier","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreCategoryStoreID'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['onlineStoreProduct'], $clientID);
		$propertyID=0;
		if($itemTypeID==0){
			//get main property ID
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",".$propertyID.",'".$clientID."', 'onlineStoreProduct', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['onlineStoreProduct']),$clientID);
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
		if($propertyID!=0){
			//create main property
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreProduct.CategoryIDs'], $clientID);
		if($propertyID==0){
			//create categoryID property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"Categories","identifiers","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreProduct.CategoryIDs'])."', NOW())";
			$result = RSquery($theQuery);
		}else{
			//edit product category property
			$theQuery = 'UPDATE rs_item_properties SET RS_TYPE="identifiers" WHERE RS_PROPERTY_ID='.$propertyID.' AND RS_CLIENT_ID='.$clientID;
			$result = RSquery($theQuery);
			//move existing records
			$theQuery = 'INSERT INTO rs_property_multiIdentifiers SELECT * FROM rs_property_identifiers WHERE RS_PROPERTY_ID='.$propertyID.' AND RS_CLIENT_ID='.$clientID;
			$result = RSquery($theQuery);
			//delete old records
			$theQuery = 'DELETE FROM rs_property_identifiers WHERE RS_PROPERTY_ID='.$propertyID.' AND RS_CLIENT_ID='.$clientID;
			$result = RSquery($theQuery);
		}
		
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreProduct.Price'], $clientID);
		if($propertyID!=0){
			//delete price property
			deleteClientProperty($propertyID, $clientID);
		}
		
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['onlineStoreAttribute'], $clientID);
		$propertyID=0;
		if($itemTypeID==0){
			//get main property ID
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",".$propertyID.",'".$clientID."', 'onlineStoreAttribute', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['onlineStoreAttribute']),$clientID);
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
		if($propertyID!=0){
			//create main property
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreAttribute.StoreProductID'], $clientID);
		if($propertyID==0){
			//create StoreProductID property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"Store Product","identifier","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreAttribute.StoreProductID'])."', NOW())";
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreAttribute.StockItemID'], $clientID);
		if($propertyID==0){
			//create StockItemID property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"Stock Item","identifier","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreAttribute.StockItemID'])."', NOW())";
			$result = RSquery($theQuery);
		}
		$propertyID = getClientPropertyID_RelatedWith_byName($definitions['onlineStoreAttribute.Price'], $clientID);
		if($propertyID==0){
			//create price property
			$propertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$propertyID.','.$categoryID.','.$clientID.',"Price","float","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"0",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$propertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['onlineStoreAttribute.Price'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		//check configuration itemtype exists
		$itemTypeID=getClientItemTypeID_RelatedWith_byName($definitions['configurationGenericModule'], $clientID);
		if($itemTypeID==0){
			//create itemtype
			$itemTypeID=getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$clientID);
			$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (".$itemTypeID.",0,'".$clientID."', 'configurationModuleGeneric', ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$clientID)).")";
			$result = RSquery($theQuery);
			//relate itemtype
			createItemTypeRelationship($itemTypeID,getAppItemTypeIDByName($definitions['configurationGenericModule']),$clientID);
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
		
		$namePropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleName'], $clientID);
		if($namePropertyID==0){
			//create property
			$namePropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$namePropertyID.','.$categoryID.','.$clientID.',"name","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$namePropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationGenericModuleName'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleDescription'], $clientID);
		if($descriptionPropertyID==0){
			//create property
			$descriptionPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$descriptionPropertyID.','.$categoryID.','.$clientID.',"description","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$descriptionPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationGenericModuleDescription'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$logoPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleLogo'], $clientID);
		if($logoPropertyID==0){
			//create property
			$logoPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$logoPropertyID.','.$categoryID.','.$clientID.',"logo","image","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$logoPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationGenericModuleLogo'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$basePropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleBase'], $clientID);
		if($basePropertyID==0){
			//create property
			$basePropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$basePropertyID.','.$categoryID.','.$clientID.',"baseItemTypes","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$basePropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationGenericModuleBase'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		$allowedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleAllowed'], $clientID);
		if($allowedPropertyID==0){
			//create property
			$allowedPropertyID=getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES ('.$allowedPropertyID.','.$categoryID.','.$clientID.',"allowedItemTypes","text","",'.getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)).',"",0,0,0)';
			$result = RSquery($theQuery);
			//relate property
			$theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('".$allowedPropertyID."', '".$clientID."', '".getAppPropertyIDByName($definitions['configurationGenericModuleAllowed'])."', NOW())";
			$result = RSquery($theQuery);
		}
		
		print ("Client with ID".$clientID."Finished!�\n");
	}

	//RETURN OK RESULT
	return "OK";

}

?>