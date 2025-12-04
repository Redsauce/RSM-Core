<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] != 0)
	{

		$theQuery = "INSERT INTO rs_lists (RS_LIST_ID, RS_CLIENT_ID, RS_NAME) VALUES (".getNextIdentification('rs_lists','RS_LIST_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",".$GLOBALS[$cstRS_POST][$cstClientID].", '".base64_decode($GLOBALS[$cstRS_POST][$cstName])."')";

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

		$result = RSQuery($theQuery);

		$results['result'] = "OK";
		$results['ID'] = getLastIdentification('rs_lists','RS_LIST_ID',$GLOBALS[$cstRS_POST][$cstClientID]);
		$results['name'] = base64_decode($GLOBALS[$cstRS_POST][$cstName]);
	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>
