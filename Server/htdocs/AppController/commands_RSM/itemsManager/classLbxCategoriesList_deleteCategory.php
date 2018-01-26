<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMmediaManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$categoryID = $GLOBALS['RS_POST']['categoryID'];


// get category properties
$propertiesList = getClientCategoryProperties($categoryID, $clientID);

// delete properties
foreach ($propertiesList as $property) {
	// delete values
	if(RSQuery('DELETE FROM '.$propertiesTables[$property['type']].' WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID) && ($property['type'] == 'image' || $property['type'] == 'file')){
		deleteMediaProperty($clientID,$property['id']);
	}

	// delete property definition
	RSQuery('DELETE FROM rs_item_properties WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);

	// delete property relationships
	RSQuery('DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);
	RSQuery('DELETE FROM rs_properties_lists WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);
	RSQuery('DELETE FROM rs_properties_groups WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);
}

// finally delete category
RSQuery('DELETE FROM rs_categories WHERE RS_CATEGORY_ID = '.$categoryID.' AND RS_CLIENT_ID = '.$clientID);


$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
