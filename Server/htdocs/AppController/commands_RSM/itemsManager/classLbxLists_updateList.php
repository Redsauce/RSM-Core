<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] > 0)
	{

		//We check if the user already exists
		$theQuery_userExists = "SELECT RS_LIST_ID FROM rs_lists WHERE RS_LIST_ID='".$GLOBALS[$cstRS_POST][$cstListID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
		$resultUsers = RSQuery($theQuery_userExists);
		if ($resultUsers->fetch_array() != 0)
			{
				// The list exists, so we update the user
				$theQuery = "UPDATE rs_lists SET RS_NAME = '".base64_decode($GLOBALS[$cstRS_POST][$cstName])."' WHERE RS_LIST_ID=".$GLOBALS[$cstRS_POST][$cstListID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

				//show query if debug mode
				if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug'])
				{
					echo $theQuery;
				}

				if($result = RSQuery($theQuery))
				{
					$results['result'] = "OK";
					$results['ID'] = $GLOBALS[$cstRS_POST][$cstListID];
					$results['name'] = base64_decode($GLOBALS[$cstRS_POST][$cstName]);
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
