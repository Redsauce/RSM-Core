<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_image_properties($clientsToUpdate){
	
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
		
		$theQuery = "SELECT `RS_ITEMTYPE_ID`, `RS_ITEM_ID`, `RS_PROPERTY_ID`, OCTET_LENGTH(`RS_DATA`) AS size FROM `rs_property_images` WHERE `RS_DATA`<>'' AND `RS_CLIENT_ID`=".$clientID;
		
		$result = RSquery($theQuery);
		
		if($result->num_rows>0){	
			while($row=$result->fetch_assoc()){
				$theQuery = "UPDATE `rs_property_images` SET `RS_NAME`='defaultImage.jpg', `RS_SIZE`=".$row['size']." WHERE `RS_CLIENT_ID`=".$clientID." AND `RS_ITEMTYPE_ID`=".$row['RS_ITEMTYPE_ID']." AND `RS_ITEM_ID`=".$row['RS_ITEM_ID']." AND `RS_PROPERTY_ID`=".$row['RS_PROPERTY_ID'];
				$res = RSquery($theQuery);
			}
		}
		
		print ("Client with ID".$clientID."Finished!�\n");
	}

	//RETURN OK RESULT
	return "OK";
}

?>