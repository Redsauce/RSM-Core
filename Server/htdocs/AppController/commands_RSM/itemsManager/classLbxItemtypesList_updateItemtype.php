<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS[$cstRS_POST][$cstClientID] > 0)
	{

		//We check if the user already exists
		$theQuery_userExists = "SELECT RS_ITEMTYPE_ID FROM rs_item_types WHERE RS_ITEMTYPE_ID='".$GLOBALS[$cstRS_POST][$cstItemtypeID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
		$resultUsers = RSquery($theQuery_userExists);
		if ($resultUsers->fetch_array() != 0)
			{
				// The itemtype exists, so we update the user		
			$theQuery = "UPDATE rs_item_types SET RS_NAME = '".base64_decode($GLOBALS[$cstRS_POST][$cstName])."', RS_ICON = ".($GLOBALS[$cstRS_POST][$cstItemtypeIcon]!=""?"0x".$GLOBALS[$cstRS_POST][$cstItemtypeIcon]:"''")." WHERE RS_ITEMTYPE_ID=".$GLOBALS[$cstRS_POST][$cstItemtypeID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
					
				if($result = RSquery($theQuery))
				{
				$results['result'] = "OK";
				$results['name'] = base64_decode($GLOBALS[$cstRS_POST][$cstName]);
				$results['itemtypeIcon'] = $GLOBALS[$cstRS_POST][$cstItemtypeIcon];
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
