<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID   = $GLOBALS[$cstRS_POST][$cstClientID];
$entries    = $GLOBALS[$cstRS_POST]['entries' ];

$results = array();

if ($entries == '') {
    $results['result'] = 'NOK';
    $results['description'] = 'No properties to update were received';
    RSReturnArrayResults($results);
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
    RSReturnArrayResults($results);
    die();
}

// Get visible properties
$visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);
$unsavedProperties = array();

// Reject malformed uploads before writing the first entry.
foreach ($entries as $entry) {
    $fields = explode(';', $entry);
    $propertyID = parsePID($fields[1], $clientID);
    if (!in_array($propertyID, $visibleProperties)) continue;
    $propertyType = getPropertyType($propertyID, $clientID);
    if ($propertyType === 'image' || $propertyType === 'file') {
        try {
            RSparseBinaryPropertyValue(base64_decode($fields[2] ?? '', true), base64_decode($fields[3] ?? '', true));
        } catch (InvalidArgumentException | LengthException $exception) {
            RSReturnArrayResults(array('result' => 'NOK', 'description' => 'Invalid or oversized file/image value', 'itemTypeID' => $itemTypeID));
        }
    }
}

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
            $result = setDataPropertyValueByID($propertyID, $itemTypeID, $entryArr[0], $clientID, base64_decode($entryArr[3], true), base64_decode($entryArr[2], true), $propertyType, $RSuserID);
        } else {                
            $result = setPropertyValueByID($propertyID, $itemTypeID, $entryArr[0], $clientID, base64_decode($entryArr[2]), $propertyType, $RSuserID);
        }
        if ($result !== 0) {
            RSReturnArrayResults(array('result' => 'NOK', 'description' => 'Unable to update item properties', 'itemTypeID' => $itemTypeID));
        }
    } else {
        $unsavedProperties[] = $propertyID;
    }
}

if ($unsavedProperties) {
    RSReturnArrayResults(array('result' => 'NOK', 'description' => 'Some requested properties are not visible', 'itemTypeID' => $itemTypeID));
}

$results['result'     ] = 'OK';
$results['description'] = "Successfully updated item " . $entryArr[0] . " of type " . $itemTypeID . ".";
$results['itemTypeID' ] = $itemTypeID;

// Return results
RSReturnArrayResults($results);
