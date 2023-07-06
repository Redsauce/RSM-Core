<?php
//*****************************************************************************
//Description:
//  Retrieves an item of the specified itemType with the associated values
//
//  PARAMETERS:
//    clientID: the client ID
//  itemTypeID: ID of the itemType to retrieve
//      itemID: ID of the item to retrieve
//*****************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// Retrieve the needed variables from the request
isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemID"]) ? $itemID     = $GLOBALS["RS_POST"]["itemID"] : dieWithError(400);

$itemTypeID     = isset($GLOBALS['RS_POST']['itemTypeID']) ? $GLOBALS['RS_POST']['itemTypeID'] : '';

$itemTypeID = ParseITID($itemTypeID, $clientID);

if ($itemTypeID == '') {
  RSreturnError("COULD NOT DETERMINE ITEM TYPE ID TO RETURN", "NOK");
}

// Return the IDs of the visible properties for user.
$visiblePropertiesIDs = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);

$properties = array();
$attributes = array();

$properties = getClientItemTypePropertiesExtended($itemTypeID, $clientID);
$categoryName = '';
$categorySet = false;

foreach ($properties as $property) {

  if (array_key_exists('category', $property)) {
    $categoryName = html_entity_decode($property['category'], ENT_COMPAT, "UTF-8");
    $categorySet = false;
  } elseif (in_array($property['id'], $visiblePropertiesIDs)) {

    if (!$categorySet && $categoryName != '') {
      $results[] = array('category' => $categoryName);
      $categorySet = true;
    }

    $value = getItemDataPropertyValue($itemID, $property['id'], $clientID);

    if (($property['type'] == 'image') || ($property['type'] == 'file')) {
      // A file needs additional properties like the file name and the file size, so let's query the database for extra attributes
      $attributes = explode(":", getItemPropertyValue($itemID, $property['id'], $clientID));
      $results[] = array(
        'ID'       => $property['id'],
        'name'     => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
        'value'    => $value, 'type' => $property['type'],
        'related'  => getAppPropertyName_RelatedWith($property['id'], $clientID),
        'filename' => array_key_exists(0, $attributes) ? $attributes[0] : '',
        'filesize' => array_key_exists(1, $attributes) ? $attributes[1] : ''
      );
    } else {
      $results[] = array(
        'ID'      => $property['id'],
        'name'    => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
        'value'   => html_entity_decode($value, ENT_COMPAT, "UTF-8"),
        'related' => getAppPropertyName_RelatedWith($property['id'], $clientID),
        'type'    => $property['type']
      );
    }
  }
}


// And write XML Response back to the application
RSreturnArrayQueryResults($results);
