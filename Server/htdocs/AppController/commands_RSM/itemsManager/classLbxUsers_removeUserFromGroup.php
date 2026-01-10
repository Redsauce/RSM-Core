<?php
//***************************************************
//Description:
//	Remove the user from a group
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";


if ($GLOBALS[$cstRS_POST][$cstClientID] != 0) {
	// check if the user exists into the client
	$theQuery_userValidation = 'SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID = '.$GLOBALS[$cstRS_POST][$cstUserID].' AND RS_CLIENT_ID = '.$GLOBALS[$cstRS_POST][$cstClientID];
	$theQuery_groupValidation = 'SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID = '.$GLOBALS[$cstRS_POST][$cstGroupID].' AND RS_CLIENT_ID = '.$GLOBALS[$cstRS_POST][$cstClientID];

	$resultUserOK = RSQuery($theQuery_userValidation);
	$resultGroupOK = RSQuery($theQuery_groupValidation);

	if (($resultUserOK->num_rows != 0) && ($resultGroupOK->num_rows != 0)) {
		//The users exists, so perform the action
		$results["result"] = removeUserFromGroup($GLOBALS[$cstRS_POST][$cstUserID], $GLOBALS[$cstRS_POST][$cstClientID], $GLOBALS[$cstRS_POST][$cstGroupID]);
	} else {
		$results["result"] = "NOK";
	}
} else {

	$results["result"] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
