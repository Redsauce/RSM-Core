<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID=$GLOBALS['RS_POST']['clientID'];
$categoryID=$GLOBALS['RS_POST']['categoryID'];

$itemTypeID = getClientCategoryItemType($categoryID, $clientID);

// get main property
$propertyID = getMainPropertyID($itemTypeID, $clientID);

//check user has access to admin pannel
$theQuery = "SELECT rs_actions.RS_ID FROM rs_actions INNER JOIN (rs_actions_clients INNER JOIN rs_actions_groups ON rs_actions_clients.RS_ID=rs_actions_groups.RS_ACTION_CLIENT_ID AND rs_actions_clients.RS_CLIENT_ID=rs_actions_groups.RS_CLIENT_ID) ON rs_actions.RS_ID=rs_actions_clients.RS_ACTION_ID WHERE rs_actions_groups.RS_CLIENT_ID=".$clientID." AND rs_actions_groups.RS_GROUP_ID IN ( SELECT rs_users_groups.RS_GROUP_ID FROM rs_users_groups INNER JOIN rs_groups ON rs_groups.RS_GROUP_ID=rs_users_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_users_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_CLIENT_ID =".$clientID." AND rs_users_groups.RS_USER_ID =".$RSuserID.") AND rs_actions.RS_NAME='rsm.mainpanel.administration.access'";

// Query the database
$results = RSquery($theQuery);

if($results->num_rows>0){
	$isAdmin=1;
	$propertiesAllowed=array();
}else{
	$isAdmin=0;
	$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $RSuserID);
}

// Now we build the query
$theQuery = "SELECT `rs_item_properties`.`RS_PROPERTY_ID` as 'ID', `rs_item_properties`.`RS_NAME` as 'NAME', `rs_item_properties`.`RS_DESCRIPTION` as 'propertyDescription', `rs_item_properties`.`RS_TYPE` as 'type', `rs_item_properties`.`RS_DEFAULTVALUE` as 'defValue', `rs_item_properties`.`RS_REFERRED_ITEMTYPE` as 'referredItemType', `rs_item_properties`.`RS_AUDIT_TRAIL` as 'auditTrail', `rs_item_properties`.`RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED` as 'auditTrailDescriptionRequired', `rs_item_properties`.`RS_AVOID_DUPLICATION` as 'avoidDuplication', `rs_item_properties`.`RS_SEARCHABLE` as 'searchable', `rs_properties_lists`.`RS_LIST_ID` as 'listID', `rs_properties_lists`.`RS_MULTIVALUES` as 'multiVal' FROM `rs_item_properties` LEFT JOIN `rs_properties_lists` USING (RS_CLIENT_ID, RS_PROPERTY_ID) WHERE `rs_item_properties`.`RS_CLIENT_ID`='" .$clientID . "' AND `rs_item_properties`.`RS_CATEGORY_ID`='".$categoryID."' ORDER BY `rs_item_properties`.`RS_ORDER`";

// Query the database
$results = RSquery($theQuery);

$array_res=array();
while($result=$results->fetch_assoc()){
	if (($result['referredItemType'] != '') && ($result['referredItemType'] != '0')) {
		$result['referredItemTypeName'] = getClientItemTypeName($result['referredItemType'], $clientID);
	} elseif ($result['type'] == 'identifier'){
		//check if its an app property
		$relatedAppPropertyID=getAppPropertyID_RelatedWith($result['ID'], $clientID);
		if($relatedAppPropertyID!='0'){
			$result['referredItemType']=getClientItemTypeID_RelatedWith(getAppPropertyReferredItemType($relatedAppPropertyID, $clientID), $clientID);
			$result['referredItemTypeName'] = getClientItemTypeName($result['referredItemType'], $clientID);
		}
	} else {
		$result['referredItemTypeName'] = '';
	}
	if($isAdmin||in_array($result['ID'], $propertiesAllowed)){
		$array_res[]=array('ID'=>$result['ID'], 'NAME'=>$result['NAME'], 'propertyDescription'=>$result['propertyDescription'], 'propertyType'=>$result['type'], 'propertyDefaultValue'=>$result['defValue'], 'propertyReferredItemType'=>$result['referredItemType'], 'propertyReferredItemTypeName'=>$result['referredItemTypeName'], 'auditTrail'=>$result['auditTrail'], 'auditTrailDescriptionRequired'=>$result['auditTrailDescriptionRequired'], 'avoidDuplication'=>$result['avoidDuplication'], 'searchable'=>$result['searchable']);
		if($result['listID']!='NULL'){
			$array_res[count($array_res)-1]['listID']=$result['listID'];
			$array_res[count($array_res)-1]['multiVal']=$result['multiVal'];
		}
		if($result['ID']==$propertyID){
			$array_res[count($array_res)-1]['mainProperty']=1;
		}
	}
}

// And write XML Response back to the application
RSReturnArrayQueryResults($array_res);
?>
