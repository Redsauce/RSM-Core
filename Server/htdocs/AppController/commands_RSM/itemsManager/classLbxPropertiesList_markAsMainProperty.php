<?php
//***************************************************
//Description:
//	updates the item properties
//  ---> updated for the v.3.10
//***************************************************

// TODO: No deberíamos necesitar un categoryID en este PHP

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

if ($GLOBALS[$cstRS_POST][$cstClientID] != 0){

	//We check if the itemtype exists into the client
	$theQuery = "SELECT rs_item_types.RS_ITEMTYPE_ID FROM rs_item_types INNER JOIN rs_categories ON rs_item_types.RS_ITEMTYPE_ID=rs_categories.RS_ITEMTYPE_ID AND rs_item_types.RS_CLIENT_ID=rs_categories.RS_CLIENT_ID WHERE rs_categories.RS_CATEGORY_ID =".$GLOBALS[$cstRS_POST][$cstCategoryID]." AND rs_categories.RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

	$result = RSQuery($theQuery);

	if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

	if ($resItem = $result->fetch_assoc()){
		//The itemtype exists, so perform the action
		//We check if the property exists into the client
		$theQuery = "SELECT RS_PROPERTY_ID, RS_TYPE FROM rs_item_properties WHERE RS_PROPERTY_ID =".$GLOBALS[$cstRS_POST][$cstPropertyID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

		$result = RSQuery($theQuery);

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']) echo $theQuery;

		if ($res = $result->fetch_assoc()){
			//The property exists
			if ($res['RS_TYPE'] != 'identifier' && $res['RS_TYPE'] != 'identifiers' && $res['RS_TYPE'] != 'file' && $res['RS_TYPE'] != 'image') {  // the main property can't be an identifier property!
				// perform the action
				$theQueryItem = "UPDATE rs_item_types SET RS_MAIN_PROPERTY_ID=".$GLOBALS[$cstRS_POST][$cstPropertyID]." WHERE RS_ITEMTYPE_ID=".$resItem['RS_ITEMTYPE_ID']." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];

				if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']){
					echo $theQueryItem;
				}

				if($result = RSQuery($theQueryItem)){
					$results['result']="OK";
				}else{
					$results["result"] = "NOK";
				}

			} else {
				$results['result'] = 'NOK';
			}
		}else{
			$results["result"] = "NOK";
		}
	}else{
		$results["result"] = "NOK";
	}
}else{
	$results["result"] = "NOK";
}
// And write XML Response back to the application
RSReturnArrayResults($results);
