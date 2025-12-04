<?php
//***************************************************
//Description:
//	Add a property to a group
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS[$cstRS_POST][$cstClientID] != 0){
	//We check if the property and the group exists for the client

	$theQuery_propertyValidation = "SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_PROPERTY_ID =".$GLOBALS[$cstRS_POST][$cstPropertyID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID =".$GLOBALS[$cstRS_POST][$cstGroupID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

	$resultpropertyOK = RSQuery($theQuery_propertyValidation);
	$resultGroupOK = RSQuery($theQuery_groupValidation);

	if(($resultpropertyOK->num_rows>0)&&($resultGroupOK->num_rows>0))
	{
		//The property exists, so perform the action
		$results["result"] = addPropertyToGroup($GLOBALS[$cstRS_POST][$cstPropertyID], $GLOBALS[$cstRS_POST][$cstGroupID],$GLOBALS[$cstRS_POST][$cstClientID]);
	}else{
		$results["result"] = "NOK";
	}

}else{
	$results["result"] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
