<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0)
	{

		$theQuery = "INSERT INTO rs_lists (RS_LIST_ID, RS_CLIENT_ID, RS_NAME) VALUES (".getNextIdentification('rs_lists','RS_LIST_ID',$GLOBALS['RS_POST']['clientID']).",".$GLOBALS['RS_POST']['clientID'].", '".base64_decode($GLOBALS['RS_POST']['name'])."')";

		if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery;

		$result = RSQuery($theQuery);

		$results['result'] = "OK";
		$results['ID'] = getLastIdentification('rs_lists','RS_LIST_ID',$GLOBALS['RS_POST']['clientID']);
		$results['name'] = base64_decode($GLOBALS['RS_POST']['name']);
	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>
