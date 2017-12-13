<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0)
	{
		$theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ICON, RS_ORDER) VALUES (".getNextIdentification('rs_item_types','RS_ITEMTYPE_ID',$GLOBALS['RS_POST']['clientID']).",0,'".$GLOBALS['RS_POST']['clientID']."', '".base64_decode($GLOBALS['RS_POST']['itemtypeName'])."', ".($GLOBALS['RS_POST']['itemtypeIcon']!=""?"0x".$GLOBALS['RS_POST']['itemtypeIcon']:"''").", ".getGenericNext('rs_item_types','RS_ORDER',array("RS_CLIENT_ID"=>$GLOBALS['RS_POST']['clientID'])).")";

		$result = RSquery($theQuery);
		$results['result'] = "OK";
		$results['itemtypeID'] = getLastIdentification('rs_item_types','RS_ITEMTYPE_ID',$GLOBALS['RS_POST']['clientID']);
		$results['itemtypeName'] = base64_decode($GLOBALS['RS_POST']['itemtypeName']);
		$results['itemtypeIcon'] = $GLOBALS['RS_POST']['itemtypeIcon'];
	} else {
		$results['result'] = "NOK";
	}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
