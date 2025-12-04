<?php
//***************************************************
//Description:
//	Add a user to a group
//  ---> updated for the v.3.10
//***************************************************


// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS[$cstRS_POST][$cstClientID] != 0)
	{
		//We check if the user exists into the client

		$theQuery_userValidation = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID ='".$GLOBALS[$cstRS_POST][$cstUserID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
		$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_ID ='".$GLOBALS[$cstRS_POST][$cstGroupID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

		$resultUserOK = RSQuery($theQuery_userValidation);
		$resultGroupOK = RSQuery($theQuery_userValidation);

		if ( ($resultUserOK->num_rows != 0) AND ($resultGroupOK->num_rows != 0) )
			{
				//The users exists, so perform the action
				$results["result"] = addUserToGroup($GLOBALS[$cstRS_POST][$cstUserID], $GLOBALS[$cstRS_POST][$cstClientID], $GLOBALS[$cstRS_POST][$cstGroupID]);
			}
		else
			{
				$results["result"] = "NOK";
			}

	}
else
	{
		$results["result"] = "NOK";
	}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
