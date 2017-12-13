<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS['RS_POST']['clientID'] > 0)
	{

		//We check if the user already exists
		$theQuery_userExists = "SELECT RS_ITEMTYPE_ID FROM rs_item_types WHERE RS_ITEMTYPE_ID='".$GLOBALS['RS_POST']['itemtypeID']."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
		$resultUsers = RSquery($theQuery_userExists);
		if ($resultUsers->fetch_array() != 0)
			{
				// The itemtype exists, so we update the user		
				$theQuery = "UPDATE rs_item_types SET RS_NAME = '".base64_decode($GLOBALS['RS_POST']['name'])."', RS_ICON = ".($GLOBALS['RS_POST']['itemtypeIcon']!=""?"0x".$GLOBALS['RS_POST']['itemtypeIcon']:"''")." WHERE RS_ITEMTYPE_ID=".$GLOBALS['RS_POST']['itemtypeID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
					
				if($result = RSquery($theQuery))
				{
					$results['result'] = "OK";
					$results['name'] = base64_decode($GLOBALS['RS_POST']['name']);
					$results['itemtypeIcon'] = $GLOBALS['RS_POST']['itemtypeIcon'];
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
