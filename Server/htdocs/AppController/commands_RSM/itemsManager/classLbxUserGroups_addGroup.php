<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0)
	{

		//We check if the user already exists
		$theQuery_groupAlreadyExists = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_NAME ='".base64_decode($GLOBALS['RS_POST']['groupName'])."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

		if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery_groupAlreadyExists;

		$result = RSQuery($theQuery_groupAlreadyExists);
		if ($result->fetch_array() != 0)
			{

				RSReturnError("GROUP ALREADY EXISTS", "6");

			}
		else
			{

				$theQuery = "INSERT INTO rs_groups (RS_GROUP_ID, RS_CLIENT_ID, RS_NAME) VALUES (".getNextIdentification('rs_groups','RS_GROUP_ID',$GLOBALS['RS_POST']['clientID']).",'".$GLOBALS['RS_POST']['clientID']."',  '".base64_decode($GLOBALS['RS_POST']['groupName'])."')";

				if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery;

				$result = RSQuery($theQuery);
				$results['result'] = "OK";
				$results['groupID'] = getLastIdentification('rs_groups','RS_GROUP_ID',$GLOBALS['RS_POST']['clientID']);
				$results['groupName'] = base64_decode($GLOBALS['RS_POST']['groupName']);
			}

	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>
