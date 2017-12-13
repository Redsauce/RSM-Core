<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$typesListID = getAppListID('stepUnitType');

//Get the id of the list client related
$clientListID = getClientListID_RelatedWith($typesListID, $clientID);

//Get all the values for this list and client
$ClientValues = getListValues($clientListID,$clientID);

//for every system list value, get the client list value
for ($i=0; $i<count($ClientValues); $i++)
{
	//get the client id value
	$AppValueID =  getAppListValueID_RelatedWith ($ClientValues[$i]['valueID'], $clientID);
	
	if ($AppValueID>0)
	{
		//Get the app value and add to client values
		$ClientValues[$i]['valueApp']=getAppValue($AppValueID);
	} else {
		//Not related. Add an empty value
		$ClientValues[$i]['valueApp']='';
	}
}

RSReturnArrayQueryResults($ClientValues);

?>