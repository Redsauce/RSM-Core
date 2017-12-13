<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Delete old relationships
$theQuery = RSQuery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = '".$GLOBALS['RS_POST']['itemTypeAppID']."' AND RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."'");

$theQuery = RSQuery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_ID = '".$GLOBALS['RS_POST']['itemTypeID']."' AND RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."'");

$theQuery = RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID IN (SELECT RS_ID FROM rs_property_app_definitions WHERE RS_ITEM_TYPE_ID = '".$GLOBALS['RS_POST']['itemTypeAppID']."') AND RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."'");

$theQuery = RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID IN (SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_CATEGORY_ID IN (SELECT RS_CATEGORY_ID FROM rs_categories WHERE RS_ITEMTYPE_ID = '".$GLOBALS['RS_POST']['itemTypeID']."' AND RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."')) AND RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."'");

$theQuery = "INSERT INTO rs_item_type_app_relations (RS_ITEMTYPE_ID, RS_CLIENT_ID, RS_ITEMTYPE_APP_ID) VALUES ('".$GLOBALS['RS_POST']['itemTypeID']."', '".$GLOBALS['RS_POST']['clientID']."', '".$GLOBALS['RS_POST']['itemTypeAppID']."');";

// Query the database
$results = RSQuery($theQuery);

$response = array();
$response['result'] = ($results == TRUE) ? "OK" : "NOK";

// And write XML Response back to the application
RSReturnArrayResults($response);
?>
