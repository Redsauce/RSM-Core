<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS['RS_POST']['clientID'] > 0)
	{

		//We check if the user already exists
		$theQuery_userExists = "SELECT RS_CATEGORY_ID FROM rs_categories WHERE RS_CATEGORY_ID='".$GLOBALS['RS_POST']['categoryID']."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
		$resultUsers = RSQuery($theQuery_userExists);
		if ($resultUsers->fetch_array() != 0)
			{
				// The user exists, so we update the user
				$theQuery = "UPDATE rs_categories SET RS_NAME = '".base64_decode($GLOBALS['RS_POST']['name'])."' WHERE RS_CATEGORY_ID=".$GLOBALS['RS_POST']['categoryID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

				//show query if debug mode
				if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug'])
				{
					echo $theQuery;
				}

				if($result = RSQuery($theQuery))
				{
					$results['result'] = "OK";
					$results['name'] = base64_decode($GLOBALS['RS_POST']['name']);
				}
				else
				{
					RSReturnError("ERROR WHILE UPDATING ITEMTYPE", "15");
				}

			}
		else
			{
				RSReturnError("ERROR WHILE UPDATING ITEMTYPE", "15");
			}

	}

else
	{
		RSReturnError("ERROR WHILE UPDATING ITEMTYPE", "15");
	}
// And write XML Response back to the application
RSReturnArrayResults($results);
?>
