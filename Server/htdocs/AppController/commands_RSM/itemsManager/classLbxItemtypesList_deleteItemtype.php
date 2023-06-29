<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];


// delete items and properties values
$items = getItemIDs($itemTypeID, $clientID);

foreach ($items as $item) {
    deleteItem($itemTypeID, $item, $clientID);
}

// delete categories and properties definitions
$propertiesList = array();
$categoriesList = getClientItemTypeCategories($itemTypeID, $clientID);

foreach ($categoriesList as $category) {
    $propertiesList = array_merge($propertiesList, getClientCategoryProperties($category['id'], $clientID));
    foreach ($propertiesList as $property) {
        // delete property definition
        RSQuery('DELETE FROM rs_item_properties WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);

        // delete property relationships
        RSQuery('DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);
        RSQuery('DELETE FROM rs_properties_lists WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);
        RSQuery('DELETE FROM rs_properties_groups WHERE RS_PROPERTY_ID = '.$property['id'].' AND RS_CLIENT_ID = '.$clientID);

    }
    RSQuery('DELETE FROM rs_categories WHERE RS_CATEGORY_ID = '.$category['id'].' AND RS_CLIENT_ID = '.$clientID);
}

// delete itemtype definition
RSQuery('DELETE FROM rs_item_types WHERE RS_ITEMTYPE_ID = '.$itemTypeID.' AND RS_CLIENT_ID = '.$clientID);

// delete itemtype relationships
RSQuery('DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_ID = '.$itemTypeID.' AND RS_CLIENT_ID = '.$clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
