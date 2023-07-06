<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// return the item type's and client's properties
//function getProperties($itemTypeID, $clientID){
//  $categories = "SELECT RS_CATEGORY_ID FROM rs_categories WHERE RS_ITEMTYPE_ID = '".$itemTypeID."' AND RS_CLIENT_ID = '".$clientID."'";
//
//  $properties = RSquery("SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_CLIENT_ID = '".$clientID."' AND RS_CATEGORY_ID IN (".$categories.");");
//
//  $props_id = array();
//  while ($row = $properties->fetch_assoc()){
//      $props[] = $row['RS_PROPERTY_ID'];
//  }
//
//  return $props_id;
//}

// Now we build the queries

// Delete old relationships
$theQuery = RSquery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = '" . $GLOBALS['RS_POST']['itemTypeAppID'] . "' AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'");

$theQuery = RSquery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_ID = '" . $GLOBALS['RS_POST']['itemTypeID'] . "' AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'");

$propertyAppDefRes = RSquery("SELECT RS_ID FROM rs_property_app_definitions WHERE RS_ITEM_TYPE_ID = '" . $GLOBALS['RS_POST']['itemTypeAppID'] . "'");
$propertyAppDefList = "";
while ($res = $propertyAppDefRes->fetch_assoc()) {
    $propertyAppDefList .= $res['RS_ID'] . ",";
}
$propertyAppDefList = rtrim($propertyAppDefList, ",");

$theQuery = RSquery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID IN (" . $propertyAppDefList . ") AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'");

$categoriesRes = RSquery("SELECT RS_CATEGORY_ID FROM rs_categories WHERE RS_ITEMTYPE_ID = '" . $GLOBALS['RS_POST']['itemTypeID'] . "' AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'");
$categoriesList = "";
while ($res = $categoriesRes->fetch_assoc()) {
    $categoriesList .= $res['RS_CATEGORY_ID'] . ",";
}
$categoriesList = rtrim($categoriesList, ",");

$propertiesRes = RSquery("SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_CATEGORY_ID IN (" . $categoriesList . ")");
$propertiesList = "";
while ($res = $propertiesRes->fetch_assoc()) {
    $propertiesList .= $res['RS_PROPERTY_ID'] . ",";
}
$propertiesList = rtrim($propertiesList, ",");

$theQuery = RSquery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID IN (" . $propertiesList . ") AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'");


// Insert new relationship
$theQuery = "INSERT INTO rs_item_type_app_relations (RS_ITEMTYPE_ID, RS_CLIENT_ID, RS_ITEMTYPE_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $GLOBALS['RS_POST']['itemTypeID'] . "', '" . $GLOBALS['RS_POST']['clientID'] . "', '" . $GLOBALS['RS_POST']['itemTypeAppID'] . "', NOW())";

// Query the database
$results = RSquery($theQuery);

$response = array();
$response['result'] = $results ? "OK" : "NOK";

// And write XML Response back to the application
RSreturnArrayResults($response);
