<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if ($GLOBALS['RS_POST']['clientID'] != 0){
	//We check if the group exists into the client

	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='".$GLOBALS['RS_POST']['groupID']."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
	$resultgroupOK = RSquery($theQuery_groupValidation);
	
	if ( $resultgroupOK->num_rows > 0 ){
		//The group exists, so perform the action
		$theQuery = "SELECT rs_actions_clients.RS_ID as 'actionID' FROM rs_actions INNER JOIN (rs_actions_clients INNER JOIN (rs_actions_groups INNER JOIN rs_groups ON rs_actions_groups.RS_GROUP_ID = rs_groups.RS_GROUP_ID AND rs_actions_groups.RS_CLIENT_ID = rs_groups.RS_CLIENT_ID) ON rs_actions_clients.RS_ID = rs_actions_groups.RS_ACTION_CLIENT_ID AND rs_actions_clients.RS_CLIENT_ID = rs_actions_groups.RS_CLIENT_ID) ON rs_actions_clients.RS_ACTION_ID = rs_actions.RS_ID WHERE rs_actions_groups.RS_GROUP_ID = '".$GLOBALS['RS_POST']['groupID']."' AND rs_actions_groups.RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

		// Query the database
		$results = RSquery($theQuery);
		RSReturnQueryResults($results);
	
	}else{
		
		$results["result"] = "NOK";
		// And write XML Response back to the application
		RSReturnArrayResults($results);
	}

}else{
	
	$results["result"] = "NOK";
	// And write XML Response back to the application
	RSReturnArrayResults($results);
}

?>