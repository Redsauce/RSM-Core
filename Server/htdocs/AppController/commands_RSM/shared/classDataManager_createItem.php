<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID   =       $GLOBALS['RS_POST']['clientID'  ];
$itemTypeID = isset($GLOBALS['RS_POST']['itemTypeID']) ? $GLOBALS['RS_POST']['itemTypeID'] : '';
$properties = isset($GLOBALS['RS_POST']['properties']) ? $GLOBALS['RS_POST']['properties'] : '';

// prepare the propertiesValues array
$propertiesValues = array();
$propertiesIDs = array();

if ($properties != '') {
  $properties = explode(',', $properties);

  foreach ($properties as $property) {
    $propertiesArr = explode(';', $property);

    // add to the propertiesValues array
    $propertiesValues[] = array('ID' => $propertiesArr[0], 'value' => base64_decode($propertiesArr[1]));
    $propertiesIDs[] = $propertiesArr[0];
  }
  //get itemType if missing
  if ($itemTypeID == '') {
    $itemTypeID = getItemTypeIDFromProperties($propertiesIDs, $clientID);
  }

  // Create the item
  $itemID = createItem($clientID, $propertiesValues);
} elseif ($itemTypeID != '') {
  // Create the item
  $itemID = createEmptyItem($itemTypeID, $clientID);
} else {
  //return empty itemId
  $itemID = '';
}

$results['itemID'] = $itemID;

if ($itemID != '') {
  // Get visible properties
  $visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);

  foreach ($visibleProperties as $property) {
    // get property type
    $propertyType = getPropertyType($property, $clientID);

    // get property value
    $propertyValue = getItemPropertyValue($itemID, $property, $clientID, $propertyType);

    // append to the results
    $results[$property] = $propertyValue;

    if (isSingleIdentifier($propertyType) || isIdentifier2itemtype($propertyType) || isIdentifier2property($propertyType)) {
      $results[$property . 'trs'] = translateSingleIdentifier($property, $propertyValue, $clientID, $propertyType);
    } elseif (isMultiIdentifier($propertyType)) {
      $results[$property . 'trs'] = translateMultiIdentifier($property, $propertyValue, $clientID);
    }
  }
}

// Return results
RSReturnArrayResults($results);
