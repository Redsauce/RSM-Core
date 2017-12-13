<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0)
	{

		$theQuery = "INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER) VALUES (".getNextIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS['RS_POST']['clientID']).",".$GLOBALS['RS_POST']['categoryID'].",".$GLOBALS['RS_POST']['clientID'].", '".base64_decode($GLOBALS['RS_POST']['propertyName'])."', '".$GLOBALS['RS_POST']['propertyType']."', '".base64_decode($GLOBALS['RS_POST']['propertyDescription'])."', ".getGenericNext('rs_item_properties','RS_ORDER',array("RS_CLIENT_ID"=>$GLOBALS['RS_POST']['clientID'], "RS_CATEGORY_ID"=>$GLOBALS['RS_POST']['categoryID'])).")";

		if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery;

		$result = RSQuery($theQuery);

		//check if a list for the property was sent
		if($GLOBALS['RS_POST']['propertyListID']!=0){
			$theQuery = "REPLACE INTO rs_properties_lists (RS_PROPERTY_ID, RS_LIST_ID, RS_CLIENT_ID) VALUES (".getLastIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS['RS_POST']['clientID']).",".$GLOBALS['RS_POST']['listID'].",".$GLOBALS['RS_POST']['clientID'].")";

			if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) echo $theQuery;

			$result = RSQuery($theQuery);
		}

		$results['result'] = "OK";
		$results['propertyID'] = getLastIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS['RS_POST']['clientID']);
		$results['name'] = base64_decode($GLOBALS['RS_POST']['propertyName']);
		$results['description'] = base64_decode($GLOBALS['RS_POST']['propertyDescription']);
		$results['type'] = $GLOBALS['RS_POST']['propertyType'];
		$results['listID'] = $GLOBALS['RS_POST']['propertyListID'];
	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>
