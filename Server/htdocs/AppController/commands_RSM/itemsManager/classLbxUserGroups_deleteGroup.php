<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0)
	{
	  	//We check if the group exists into the client
		$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='".$GLOBALS['RS_POST']['groupID']."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

		if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery_groupValidation;

		$resultGroupsOK = RSQuery($theQuery_groupValidation);

		if ( $resultGroupsOK->fetch_array() != 0 )
			{

				// delete group relationships
				$result = RSQuery('DELETE FROM rs_users_groups WHERE RS_CLIENT_ID = '.$GLOBALS['RS_POST']['clientID'].' AND RS_GROUP_ID = '.$GLOBALS['RS_POST']['groupID']);

				$result = RSQuery('DELETE FROM rs_actions_groups WHERE RS_CLIENT_ID = '.$GLOBALS['RS_POST']['clientID'].' AND RS_GROUP_ID = '.$GLOBALS['RS_POST']['groupID']);

				// delete group
				$theQuery = "DELETE FROM rs_groups WHERE RS_GROUP_ID=".$GLOBALS['RS_POST']['groupID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

				if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery;
				$result = RSQuery($theQuery);
				$results['result'] = "OK";
			}
		else
			{
				RSReturnError("ERROR WHILE DELETING GROUP", "8");
			}
	}
else
	{
		$results['result'] = "NOK";
	}

// And write XML Response back to the application
RSReturnArrayResults($results);

?>
