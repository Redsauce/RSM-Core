<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] != 0) {

		// check if the value already exist
	$theQuery_valueExists = 'SELECT RS_VALUE_ID FROM rs_property_values WHERE RS_CLIENT_ID='.$GLOBALS[$cstRS_POST][$cstClientID].' AND RS_LIST_ID='.$GLOBALS[$cstRS_POST][$cstListID].' AND RS_VALUE= "'.base64_decode($GLOBALS[$cstRS_POST][$cstValue]).'"';

		$result = RSQuery($theQuery_valueExists);

		if ($result->num_rows == 0) {

			$theQuery = "INSERT INTO rs_property_values (RS_VALUE_ID, RS_LIST_ID, RS_CLIENT_ID, RS_VALUE, RS_ORDER) VALUES (".getNextIdentification('rs_property_values','RS_VALUE_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",".$GLOBALS[$cstRS_POST][$cstListID].",".$GLOBALS[$cstRS_POST][$cstClientID].", '".base64_decode($GLOBALS[$cstRS_POST][$cstValue])."', ".getGenericNext('rs_property_values','RS_ORDER',array("RS_CLIENT_ID"=>$GLOBALS[$cstRS_POST][$cstClientID], "RS_LIST_ID"=>$GLOBALS[$cstRS_POST][$cstListID])).")";

			if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

			$result = RSQuery($theQuery);

			$results['result'] = "OK";
			$results['ID'] = getLastIdentification('rs_property_values','RS_VALUE_ID',$GLOBALS[$cstRS_POST][$cstClientID]);
			$results['value'] = base64_decode($GLOBALS[$cstRS_POST][$cstValue]);
		} else {

			$results['result'] = "NOK2";
			$results['value'] = base64_decode($GLOBALS[$cstRS_POST][$cstValue]);
		}

} else {

	$results['result'] = "NOK1";
}


// And write XML Response back to the application
RSReturnArrayResults($results);
