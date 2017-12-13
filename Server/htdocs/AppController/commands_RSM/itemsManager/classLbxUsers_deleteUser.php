<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0
if($GLOBALS['RS_POST']['clientID']!=0){
	//We check if the user exists into the client
	$theQuery_userValidation = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID =".$GLOBALS['RS_POST']['userID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

	if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']){
		echo $theQuery_userValidation;
	}

	$resultUserOK=RSQuery($theQuery_userValidation);

	if($resultUserOK->num_rows>0){
		$resultUser=$resultUserOK->fetch_assoc();

		// Delete user relationships
		$result = RSQuery('DELETE FROM rs_users_groups WHERE RS_CLIENT_ID = '.$GLOBALS['RS_POST']['clientID'].' AND RS_USER_ID = '.$GLOBALS['RS_POST']['userID']);

		// Delete user
		$theQuery="DELETE FROM rs_users WHERE RS_USER_ID=".$GLOBALS['RS_POST']['userID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

		if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']){
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
?>
