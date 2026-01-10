<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0
if($GLOBALS[$cstRS_POST][$cstClientID]!=0){
	//We check if the user exists into the client
	$theQuery_userValidation = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID =".$GLOBALS[$cstRS_POST][$cstUserID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

	if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']){
		echo $theQuery_userValidation;
	}

	$resultUserOK=RSQuery($theQuery_userValidation);

	if($resultUserOK->num_rows>0){
		$resultUser=$resultUserOK->fetch_assoc();

		// Delete user relationships
		$result = RSQuery('DELETE FROM rs_users_groups WHERE RS_CLIENT_ID = '.$GLOBALS[$cstRS_POST][$cstClientID].' AND RS_USER_ID = '.$GLOBALS[$cstRS_POST][$cstUserID]);

		// Delete user
		$theQuery="DELETE FROM rs_users WHERE RS_USER_ID=".$GLOBALS[$cstRS_POST][$cstUserID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']){
			echo $theQuery_userValidation;
		}

		if ($result=RSQuery($theQuery)){
			// We mark the item associated as deleted
			$data["result"] = "OK";

		}else{
			$data["result"] = "NOK";
		}

	}else{
		$data["result"] = "NOK";
	}
}else{
	$data["result"] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($data);
