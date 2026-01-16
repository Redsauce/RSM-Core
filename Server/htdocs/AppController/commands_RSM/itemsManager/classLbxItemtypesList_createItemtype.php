<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] != 0)
	{
		$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ICON, RS_ORDER) VALUES (".getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",0,'".$GLOBALS[$cstRS_POST][$cstClientID]."', '".base64_decode($GLOBALS[$cstRS_POST]['itemtypeName'])."', ".($GLOBALS[$cstRS_POST]['itemtypeIcon']!=""?"0x".$GLOBALS[$cstRS_POST]['itemtypeIcon']:"''").", ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$GLOBALS[$cstRS_POST][$cstClientID])).")";

		$result = RSquery($theQuery);
		$results['result'] = "OK";
		$results['itemtypeID'] = getLastIdentification('rs_item_types','RS_ITEMTYPE_ID',$GLOBALS[$cstRS_POST][$cstClientID]);
		$results['itemtypeName'] = base64_decode($GLOBALS[$cstRS_POST]['itemtypeName']);
		$results['itemtypeIcon'] = $GLOBALS[$cstRS_POST]['itemtypeIcon'];
	} else {
		$results['result'] = "NOK";
	}

// And write XML Response back to the application
RSReturnArrayResults($results);
