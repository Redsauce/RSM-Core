<?php
//***************************************************
//Description:
//	update the item property
//***************************************************

// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RStools.php';


isset($GLOBALS[$cstRS_POST][$cstClientID]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyListID]) ? $listID = $GLOBALS[$cstRS_POST][$cstPropertyListID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyID]) ? $propertyID = $GLOBALS[$cstRS_POST][$cstPropertyID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyName]) ? $propertyName = base64_decode($GLOBALS[$cstRS_POST][$cstPropertyName]) : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyDescription]) ? $propertyDescription = base64_decode($GLOBALS[$cstRS_POST][$cstPropertyDescription]) : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyMultiVal]) ? $propertyMultiValue = $GLOBALS[$cstRS_POST][$cstPropertyMultiVal] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstUpdatePrevious]) ? $updatePrevious = $GLOBALS[$cstRS_POST][$cstUpdatePrevious] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyAuditTrail]) ? $propertyAuditTrail = $GLOBALS[$cstRS_POST][$cstPropertyAuditTrail] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyAuditTrailDescriptionRequired]) ? $propertyAuditTrailDescriptionRequired = $GLOBALS[$cstRS_POST][$cstPropertyAuditTrailDescriptionRequired] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstConfirmDuplicated]) ? $confirmDuplicated = $GLOBALS[$cstRS_POST][$cstConfirmDuplicated] : dieWithError(400);
(isset($GLOBALS[$cstRS_POST][$cstAvoidDuplication]) && $GLOBALS[$cstRS_POST][$cstAvoidDuplication] == 1) ? $avoidDuplicateProperty = 1 : $avoidDuplicateProperty = 0;
(isset($GLOBALS[$cstRS_POST][$cstSearchable]) && $GLOBALS[$cstRS_POST][$cstSearchable] == 0) ? $isSearchableProperty = 0 : $isSearchableProperty = 1;

// get the item type
$itemTypeID = getClientPropertyItemType($propertyID, $clientID);

//check property name exists
if($confirmDuplicated != '1'){
	$itemTypeProperties=getClientItemTypeProperties($itemTypeID,$clientID);
	foreach($itemTypeProperties as $itemTypeProperty){
		if($itemTypeProperty['name']==$propertyName&&$itemTypeProperty['id']!=$propertyID){

			$results['result'] = 'NOK';
			$results['description'] = 'NAME_ALREADY_EXISTS';
			// And write XML Response back to the application
			RSReturnArrayResults($results);
			// Terminate PHP execution
			exit;
		}
	}
}

// get property type
$propertyType = getPropertyType($propertyID, $clientID);

// check default value match property type and set to type default value otherwise
$propertyDefaultValue = checkType(base64_decode($GLOBALS[$cstRS_POST]['propertyDefaultVal']), $propertyType);

// save the previous property default value
$propertyPrevDefValue = getClientPropertyDefaultValue($propertyID, $clientID);

// update the property
$result = RSquery('UPDATE rs_item_properties SET RS_NAME = "'.$propertyName.'", RS_DESCRIPTION = "'.$propertyDescription.'", RS_DEFAULTVALUE = "'.$propertyDefaultValue.'", RS_AUDIT_TRAIL = '.$propertyAuditTrail.', RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED = '.$propertyAuditTrailDescriptionRequired.', RS_AVOID_DUPLICATION = '.$avoidDuplicateProperty.', RS_SEARCHABLE = '.$isSearchableProperty.' WHERE RS_PROPERTY_ID = '.$propertyID.' AND RS_CLIENT_ID = '.$clientID);
// check if a list for the property was sent
if ($listID != 0) {
	//if (RSQuery("SELECT RS_LIST_ID FROM rs_properties_lists WHERE RS_PROPERTY_ID = ".$propertyID." AND RS_CLIENT_ID = ".$clientID->num_rows) > 0) {
	//	RSQuery("UPDATE rs_properties_lists SET RS_LIST_ID = ".$listID.", RS_MULTIVALUES = ".$propertyMultiValue." WHERE RS_PROPERTY_ID = ".$propertyID." AND RS_CLIENT_ID = ".$clientID);
	//} else {
	//	RSQuery("INSERT IGNORE INTO rs_properties_lists (RS_PROPERTY_ID, RS_LIST_ID, RS_CLIENT_ID, RS_MULTIVALUES) VALUES (".$propertyID.",".$listID.",".$clientID.",".$propertyMultiValue.")");
	//}
	$result = RSquery("REPLACE INTO rs_properties_lists (RS_PROPERTY_ID, RS_LIST_ID, RS_CLIENT_ID, RS_MULTIVALUES) VALUES (".$propertyID.",".$listID.",".$clientID.",".$propertyMultiValue.")");
} else $result = RSquery("DELETE FROM rs_properties_lists WHERE RS_PROPERTY_ID = ".$propertyID." AND RS_CLIENT_ID = ".$clientID);

if ($updatePrevious == '1') {
	// update the property default value for the items that already exist
	$itemTypeID = getClientPropertyItemType($propertyID, $clientID);
	$itemIDs = IQ_getItemIDs($itemTypeID, $clientID);
	while ($row = $itemIDs->fetch_assoc()) {
		if (getItemPropertyValue($row['ID'], $propertyID, $clientID, $propertyType) == $propertyPrevDefValue) {
			setPropertyValueByID($propertyID, $itemTypeID, $row['ID'], $clientID, $propertyDefaultValue, $propertyType, $RSuserID);
		}
	}
}

$results['result'                       ] = "OK";
$results['name'                         ] = $propertyName;
$results['description'                  ] = $propertyDescription;
$results['listID'                       ] = $listID;
$results['multiVal'                     ] = $propertyMultiValue;
$results['defaultValue'                 ] = $propertyDefaultValue;
$results['auditTrail'                   ] = $propertyAuditTrail;
$results['auditTrailDescriptionRequired'] = $propertyAuditTrailDescriptionRequired;
$results['avoidDuplication'             ] = $avoidDuplicateProperty;
$results['searchable'                   ] = $isSearchableProperty;

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
