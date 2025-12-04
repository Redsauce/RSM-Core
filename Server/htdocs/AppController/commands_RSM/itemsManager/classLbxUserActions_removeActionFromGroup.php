<?php
//***************************************************
//Description:
//	Detach the action from a group
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";


if ($GLOBALS[$cstRS_POST][$cstClientID] != 0){
	//We check if the group exists for the client
	$theQuery_actionValidation = "SELECT RS_ID FROM rs_actions_clients WHERE RS_ID =".$GLOBALS[$cstRS_POST][$cstActionID];
	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID =".$GLOBALS[$cstRS_POST][$cstGroupID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
	
	$resultActionOK = RSquery($theQuery_actionValidation);
	$resultGroupOK = RSquery($theQuery_groupValidation);
			
	if ( ($resultActionOK->num_rows > 0) AND ($resultGroupOK->num_rows > 0) ){
		//The action exists, so perform the action
		$results["result"] = removeActionFromGroup($GLOBALS[$cstRS_POST][$cstActionID], $GLOBALS[$cstRS_POST][$cstGroupID], $GLOBALS[$cstRS_POST][$cstClientID]);
	
	}else{
		$results["result"] = "NOK";
	}

}else{
	$results["result"] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>