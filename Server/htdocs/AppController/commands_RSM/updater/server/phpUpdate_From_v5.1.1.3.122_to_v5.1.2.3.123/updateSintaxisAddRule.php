<?php
//***************************************************
//Description:
//	 Update all event scripts replacing .addrule with .addFilterRule
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_sintaxis_addrule($clientsToUpdate){
	
	global $definitions;
	
	if(count($clientsToUpdate)==0){
		print ("No specific clients defined, processing all clients: \n");
		// get all clients
		$theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");
		
		while($client=$theQuery->fetch_assoc()) {
			$clientsToUpdate[]=$client['RS_ID'];
		}
	}
	
	print ("Selected clients to update: \n");
	print_r($clientsToUpdate);
	
	// Update the system property
	//$theAditionQuery = "UPDATE `rs_property_app_definitions` SET `RS_TYPE` = 'date' WHERE `RS_NAME` = 'invoice.provider.paymentDate'";
	//$result = $mysqli->query($theAditionQuery);
		
	//Update for the defined clients
	for ($cliNum=0;$cliNum<count($clientsToUpdate);$cliNum++){
		
		//Get the clientID
		$clientID = $clientsToUpdate[$cliNum];
		print ("Starting process for client ".$clientID."\n");
		
		//get properties related with event.actions
		$theQuery = "SELECT RS_PROPERTY_ID FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = (SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'event.actions' LIMIT 1) AND RS_CLIENT_ID = ".$clientID;
		$propertiesResults = $mysqli->query($theQuery);
				
		if($propertiesResults->num_rows>0){
		    $propertyResult = $propertiesResults->fetch_assoc();
			
			// Convert sintaxis into the events
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.addrule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.AddRule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.Addrule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.addRule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);		
		}
		
		//get properties related with eventInclude.actions
		$theQuery = "SELECT RS_PROPERTY_ID FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = (SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'eventInclude.actions' LIMIT 1) AND RS_CLIENT_ID = ".$clientID;
		$propertiesResults = $mysqli->query($theQuery);
				
		if($propertiesResults->num_rows>0){
		    $propertyResult = $propertiesResults->fetch_assoc();
			
			// Convert sintaxis into the events
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.addrule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.AddRule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.Addrule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);
			
			$theNewPropertiesQuery = "UPDATE  `rs_property_longtext` SET `RS_DATA` = replace(`RS_DATA`, '.addRule', '.addFilterRule') WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
			$result = $mysqli->query($theNewPropertiesQuery);						
		}
	
		
		print ("Client with ID ".$clientID." updated sintaxis!\n");
	}

	//RETURN OK RESULT
	return "OK";	
}
?>