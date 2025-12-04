<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS[$cstRS_POST][$cstClientID] != 0)
	{

		$theQuery = "INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER) VALUES (".getNextIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",".$GLOBALS[$cstRS_POST][$cstCategoryID].",".$GLOBALS[$cstRS_POST][$cstClientID].", '".base64_decode($GLOBALS[$cstRS_POST][$cstPropertyName])."', '".$GLOBALS[$cstRS_POST][$cstPropertyType]."', '".base64_decode($GLOBALS[$cstRS_POST][$cstPropertyDescription])."', ".getGenericNext('rs_item_properties','RS_ORDER',array("RS_CLIENT_ID"=>$GLOBALS[$cstRS_POST][$cstClientID], "RS_CATEGORY_ID"=>$GLOBALS[$cstRS_POST][$cstCategoryID])).")";

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

		$result = RSQuery($theQuery);

		//check if a list for the property was sent
		if($GLOBALS[$cstRS_POST][$cstPropertyListID]!=0){
			$theQuery = "REPLACE INTO rs_properties_lists (RS_PROPERTY_ID, RS_LIST_ID, RS_CLIENT_ID) VALUES (".getLastIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS[$cstRS_POST][$cstClientID]).",".$GLOBALS[$cstRS_POST][$cstListID].",".$GLOBALS[$cstRS_POST][$cstClientID].")";

			if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

			$result = RSQuery($theQuery);
		}

		$results['result'] = "OK";
	$results['propertyID'] = getLastIdentification('rs_item_properties','RS_PROPERTY_ID',$GLOBALS[$cstRS_POST][$cstClientID]);
	$results['name'] = base64_decode($GLOBALS[$cstRS_POST][$cstPropertyName]);
	$results['description'] = base64_decode($GLOBALS[$cstRS_POST][$cstPropertyDescription]);
	$results['type'] = $GLOBALS[$cstRS_POST][$cstPropertyType];
	$results['listID'] = $GLOBALS[$cstRS_POST][$cstPropertyListID];
	}
else
	{
		$results['result'] = "NOK";
	}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>
