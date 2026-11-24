<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID   =       $GLOBALS[$cstRS_POST]['clientID'  ];
$itemTypeID = isset($GLOBALS[$cstRS_POST][$cstItemTypeID])? $GLOBALS[$cstRS_POST][$cstItemTypeID] : '';
$properties = isset($GLOBALS[$cstRS_POST]['properties'])? $GLOBALS[$cstRS_POST]['properties'] : '';

// prepare the propertiesValues array
$propertiesValues = array();
$propertiesIDs = array();
$invalidProperties = array('result' => 'NOK', 'description' => 'Invalid properties format', 'itemID' => 0);

if (!is_string($properties)) {
    RSReturnArrayResults($invalidProperties);
    exit();
}

if ($properties != '') {
    $properties = explode(',', $properties);

    foreach ($properties as $property) {
        // Ignore empty separators, including a trailing comma from legacy clients.
        if ($property === '') {
            continue;
        }
        $propertiesArr = explode(';', $property);
        // An empty encoded value is valid ("ID;"); a missing separator is not.
        if (count($propertiesArr) < 2 || $propertiesArr[0] === '') {
            RSReturnArrayResults($invalidProperties);
            exit();
        }

        // add to the propertiesValues array
        $propertiesValues[] = array('ID' => $propertiesArr[0], 'value' => base64_decode($propertiesArr[1]));
        $propertiesIDs[] = $propertiesArr[0];
    }
    if (empty($propertiesValues)) {
        RSReturnArrayResults($invalidProperties);
        exit();
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
if ($itemID === 0) {
    $results['result'] = 'NOK';
    $results['description'] = 'Unable to create item';
    RSReturnArrayResults($results);
    exit();
}

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
