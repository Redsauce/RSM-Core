<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if ($GLOBALS[$cstRS_POST][$cstClientID] != 0){
	
	//We check if the group exists into the client
	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='".$GLOBALS[$cstRS_POST][$cstGroupID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
	$resultgroupOK = RSquery($theQuery_groupValidation);
	
	if($resultgroupOK->num_rows != 0){
		
		//The group exists, so perform the action
		$theQuery = "SELECT rs_users_groups.RS_USER_ID as 'userID', rs_users_groups.RS_GROUP_ID as 'ID' FROM rs_users_groups INNER JOIN rs_groups ON rs_users_groups.RS_GROUP_ID = rs_groups.RS_GROUP_ID AND rs_users_groups.RS_CLIENT_ID = rs_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_GROUP_ID =".$GLOBALS[$cstRS_POST][$cstGroupID]." AND rs_users_groups.RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

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
