<?php
//***************************************************
//Description:
//	Detach the action from a group
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";


if ($GLOBALS['RS_POST']['clientID'] != 0){
	//We check if the group exists for the client
	$theQuery_actionValidation = "SELECT RS_ID FROM rs_actions_clients WHERE RS_ID =".$GLOBALS['RS_POST']['actionID'];
	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID =".$GLOBALS['RS_POST']['groupID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
	
	$resultActionOK = RSquery($theQuery_actionValidation);
	$resultGroupOK = RSquery($theQuery_groupValidation);
			
	if ( ($resultActionOK->num_rows > 0) AND ($resultGroupOK->num_rows > 0) ){
		//The action exists, so perform the action
		$results["result"] = removeActionFromGroup($GLOBALS['RS_POST']['actionID'], $GLOBALS['RS_POST']['groupID'], $GLOBALS['RS_POST']['clientID']);
	
	}else{
		$results["result"] = "NOK";
	}

}else{
	$results["result"] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>