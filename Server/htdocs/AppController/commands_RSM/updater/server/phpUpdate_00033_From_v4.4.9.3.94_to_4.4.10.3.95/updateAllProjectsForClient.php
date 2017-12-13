<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

// Database connection startup
include "../../utilities/RSdatabase.php";
include "../../utilities/RSMitemsManagement.php";

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_relations($clientsToUpdate){
	
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
		
		// get item type
		$itemTypeProjectsID = getClientItemTypeID_RelatedWith_byName($definitions['projects'], $clientID);
			
		// get property
		$clientPropertyID = getClientPropertyID_RelatedWith_byName($definitions['projectClient'], $clientID);
		
		//check if client has the property
		if($clientPropertyID!='0'){
			
			//First, get all projects with its client property
			// build return properties array
			$returnProperties = array();
			$returnProperties[] = array('ID' => $clientPropertyID, 'name' => 'clientID');
			
			//build filter array
			$filters = array();
			
			//get projects
			$projects = getFilteredItemsIDs($itemTypeProjectsID, $clientID, $filters, $returnProperties);
			
			//now update property type
			$theQuery = "UPDATE `rs_item_properties` SET `RS_TYPE`='identifiers' WHERE `RS_CLIENT_ID`=".$clientID." AND `RS_PROPERTY_ID`=".$clientPropertyID;
	
			//Query the database
			$results = RSquery($theQuery);
			
			//now save the new properties
			foreach($projects as $project){
				setPropertyValueByID($clientPropertyID, $itemTypeProjectsID, $project['ID'], $clientID, $project['clientID']);
			}
			
			//clean old properties
			$theQuery = "DELETE FROM `".$propertiesTables['identifiers']."` WHERE `RS_CLIENT_ID`=".$clientID." AND `RS_PROPERTY_ID`=".$clientPropertyID;
	
			//Query the database
			$results = RSquery($theQuery);
			
			print ("Client with ID".$clientID."Finished!�\n");
		}
	}

	//RETURN OK RESULT
	return "OK";

}
?>