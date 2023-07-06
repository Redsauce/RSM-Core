<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$entries    = $GLOBALS['RS_POST']['entries'];

$results = array();

if ($entries == '') {
  $results['result'] = 'NOK';
  $results['description'] = 'No properties to update were received';
  RSreturnArrayResults($results);
  die();
}

$entries = explode(',', $entries);
$propertiesID  = array();

// Get the itemType with an array of propertyIDs
// Construct the array to use the function getItemTypeFromProperties
foreach ($entries as $entry) {
  $exploded = explode(';', $entry);
  $propertiesID[] = $exploded[1];
}

$itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);

if ($itemTypeID == '') {
  $results['result'] = 'NOK';
  $results['description'] = 'Could not determine the item type to return.';
  RSreturnArrayResults($results);
  die();
}

// Get visible properties
$visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);
$unsavedProperties = array();

foreach ($entries as $entry) {
  // get entry data
  $entryArr = explode(';', $entry);

  // REMEMBER:
  // entryArr[0] = item ID
  // entryArr[1] = property ID
  // entryArr[2] = base64-encoded property value
  // entryArr[3] = (for images/files only) base64-encoded property name
  // entryArr[4] = (for images/files only) base64-encoded property size

  $propertyID = parsePID($entryArr[1], $clientID);

  if (in_array($propertyID, $visibleProperties)) {
    // save the property value
    $propertyType = getPropertyType($propertyID, $clientID);
    if ($propertyType == 'image' || $propertyType == 'file') {
      setDataPropertyValueByID($propertyID, $itemTypeID, $entryArr[0], $clientID, base64_decode($entryArr[3]), base64_decode($entryArr[2]), $propertyType);
    } else {
      setPropertyValueByID($propertyID, $itemTypeID, $entryArr[0], $clientID, base64_decode($entryArr[2]), $propertyType, $RSuserID);
    }
  } else {
    $unsavedProperties[] = $propertyID;
  }
}

// TODO: avisar al usuario que no se han podido guardar algunos valores (los que corresponden a las propiedades a las que no tiene acceso)

$results['result'] = 'OK';
$results['description'] = "Successfully updated item " . $entryArr[0] . " of type " . $itemTypeID . ".";

// Return results
RSreturnArrayResults($results);
