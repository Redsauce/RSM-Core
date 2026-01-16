<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] != 0)
	{

		//We check if the user already exists
		$theQuery_groupAlreadyExists = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_NAME ='".base64_decode($GLOBALS[$cstRS_POST][$cstGroupName])."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery_groupAlreadyExists;

		$result = RSQuery($theQuery_groupAlreadyExists);
		if ($result->fetch_array() != 0)
			{

				RSReturnError("GROUP ALREADY EXISTS", "6");

			}
		else
			{

				$theQuery = "INSERT INTO rs_groups (RS_GROUP_ID, RS_CLIENT_ID, RS_NAME) VALUES (".getNextIdentification('rs_groups','RS_GROUP_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",'".$GLOBALS[$cstRS_POST][$cstClientID]."',  '".base64_decode($GLOBALS[$cstRS_POST][$cstGroupName])."')";

				if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

				$result = RSQuery($theQuery);
				$results['result'] = "OK";
				$results['groupID'] = getLastIdentification('rs_groups','RS_GROUP_ID',$GLOBALS[$cstRS_POST][$cstClientID]);
				$results['groupName'] = base64_decode($GLOBALS[$cstRS_POST][$cstGroupName]);
			}

	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
