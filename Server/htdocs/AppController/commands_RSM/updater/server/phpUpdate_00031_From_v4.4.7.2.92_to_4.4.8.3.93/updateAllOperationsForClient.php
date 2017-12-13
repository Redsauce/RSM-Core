<?php
//***************************************************
//Description:
//	 Update all relations. Add testCategories for the passed client
//***************************************************

// Database connection startup
include "../../utilities/RSdatabase.php";
include "../../utilities/RSMitemsManagement.php";
include "../../utilities/RSMbankCodes.php";

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_relations($clientsToUpdate){
global $definitions;
	
	if(count($clientsToUpdate)==0){
		print ("No specific clients defined, processing all clients: �_n");
		// get all clients
		$theQuery = RSQuery('SELECT `RS_ID` FROM `rs_clients`');
		
		while($client=$theQuery->fetch_assoc()) {
			$clientsToUpdate[]=array($client['RS_ID'],0);
		}
	}
	
	print ("Selected clients to update: �_n");
	print_r($clientsToUpdate);
	
	//Update the defined clients
	for ($cliNum=0;$cliNum<count($clientsToUpdate);$cliNum++){
		
		//Get the clientID
		$clientID = $clientsToUpdate[$cliNum][0];
		
		print ("Starting process for client".$clientID."�_n");
		
		// get item types
		$cashRegistersItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashRegisters'], $clientID);
		$accountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);
		$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
		$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);
			
		// get properties
		$subAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID);
		$cashIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationCashID'], $clientID);
		
		//check operationCashID property created & related
		if($cashIDPropertyID==0||$cashIDPropertyID==""){
			//create client property
			//get first category
			$theQuery = 'SELECT `RS_CATEGORY_ID` as "ID" FROM `rs_categories` WHERE `RS_CLIENT_ID`="' . $clientID . '" AND `RS_ITEMTYPE_ID`="' . $operationsItemTypeID . '" ORDER BY `RS_ORDER`';
			$operationCategories = RSQuery($theQuery);
			if($operationCategory=$operationCategories->fetch_assoc()){
				$categoryID=$operationCategory['ID'];
			}
		
			$newPropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
			$newPropertyOrder = getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID));
			
			// build create property query
			$theQuery = 'INSERT INTO `rs_item_properties` (`RS_PROPERTY_ID`, `RS_CATEGORY_ID`, `RS_CLIENT_ID`, `RS_NAME`, `RS_TYPE`, `RS_DESCRIPTION`, `RS_ORDER`, `RS_DEFAULTVALUE`, `RS_REFERRED_ITEMTYPE`, `RS_AUDIT_TRAIL`, `RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED`) VALUES ('.$newPropertyID.','.$categoryID.','.$clientID.',"cashRegisterID","identifier","",'.$newPropertyOrder.',"",0,0,0)';
							
			// execute the query
			if(RSQuery($theQuery)){
				$cashIDPropertyID=$newPropertyID;
			}else{
				$cashIDPropertyID=0;
			}
			
			// insert the new property for the items that already exists
			$itemIDs = IQ_getItemIDs($operationsItemTypeID, $clientID);
		
			if ($itemIDs->num_rows > 0) {
				
				$row = $itemIDs->fetch_assoc();
				
				// build the insert property query
				$theQuery = 'INSERT INTO `'.$propertiesTables["identifier"].'` (`RS_ITEMTYPE_ID`, `RS_ITEM_ID`, `RS_PROPERTY_ID`, `RS_DATA`, `RS_CLIENT_ID`) VALUES ('.$operationsItemTypeID.','.$row['ID'].','.$newPropertyID.',"",'.$clientID.')';
				
				while ($row = $itemIDs->fetch_assoc()) {
					$theQuery .= ',('.$operationsItemTypeID.','.$row['ID'].','.$newPropertyID.',"",'.$clientID.')';
				}
				
				// execute the query
				$result = RSQuery($theQuery);
			}
			
			//create property relation
			$result = RSQuery('SELECT `RS_ID` FROM `rs_property_app_definitions` WHERE `RS_NAME` = "operationCashID"');
			$appPropertyID = $result->fetch_assoc();
			createPropertyRelationship($cashIDPropertyID, $appPropertyID['RS_ID'], $clientID);
		}
		
		//check or get cashID
		if($clientsToUpdate[$cliNum][1]==0||$clientsToUpdate[$cliNum][1]==""){
			//get first cash item ID
			$cashRegisterIDs = getItemIDs($cashRegistersItemTypeID,$clientID);
			$clientsToUpdate[$cliNum][1]=$cashRegisterIDs[0];
		}
		
		//get the 430 accounts
		$filterProperties = array();
		$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['accountType'], $clientID), 'value' => $clientsCode.'%', 'mode' => 'LIKE');
		// get the accounts ID associated with the "430"
		$result = IQ_getFilteredItemsIDs($accountsItemTypeID, $clientID, $filterProperties, array());
		
		$clientsAccounts = array();
		while ($row = $result->fetch_assoc()) {
			$clientsAccounts[] = $row['ID'];
		}
		
		if (count($clientsAccounts) > 0) {
			$accounts = implode(',', $clientsAccounts);
		} else {
			$accounts = '';
		}
		
		// build filter properties array for subaccounts
		$filterProperties = array();
		$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accounts, 'mode' => '<-IN');
		
		// get the subaccounts ID associated to the "clients"
		$result = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());
		
		$clientsSubAccounts = array();
		while ($row = $result->fetch_assoc()) {
			$clientsSubAccounts[] = $row['ID'];
		}
		
		if (count($clientsSubAccounts) > 0) {
			$subAccounts = implode(',', $clientsSubAccounts);
		} else {
			$subAccounts = '';
		}

		// build filter properties array for operations
		$filterProperties = array();
		$filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => $subAccounts, 'mode' => '<-IN');
		
		// get the operations ID associated to the "clients"
		$result = IQ_getFilteredItemsIDs($operationsItemTypeID, $clientID, $filterProperties, array());
		
		while ($row = $result->fetch_assoc()) {
			print ("--------------------------�_n");
			print ("Updated operation ID:".$row['ID']."�_n");
			setPropertyValueByID($cashIDPropertyID, $operationsItemTypeID, $row['ID'], $clientID, $clientsToUpdate[$cliNum][1], '', $RSuserID);		
			print "--------------------------�_n";
		}
		
		print ("Client with ID".$clientID."Finished!�_n");

	}

//RETURN OK RESULT
return "OK";

}
?>