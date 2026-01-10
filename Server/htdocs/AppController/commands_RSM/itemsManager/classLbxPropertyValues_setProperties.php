<?php
//***************************************************
//Description:
//	updates the item properties
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";


isset($GLOBALS[$cstRS_POST][$cstClientID]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemID]) ? $itemID = $GLOBALS[$cstRS_POST][$cstItemID] : dieWithError(400);

$err = 1;

// If the passed item type is a sustem property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

// update item properties
for ($i = 0; isset($GLOBALS[$cstRS_POST]['propertyID' . $i]); $i++) {
    $propertyID = $GLOBALS[$cstRS_POST]['propertyID' . $i];
    isset($GLOBALS[$cstRS_POST]['propertyType' . $i]) ? $propertyType    = $GLOBALS[$cstRS_POST]['propertyType' . $i]  : dieWithError(400);
    isset($GLOBALS[$cstRS_POST]['data'         . $i]) ? $propertyData    = $GLOBALS[$cstRS_POST]['data'         . $i]  : dieWithError(400);
    isset($GLOBALS[$cstRS_POST]['name'         . $i]) ? $propertyName    = $GLOBALS[$cstRS_POST]['name'         . $i]  : $propertyName = '';

    if ($propertyType != 'image' && $propertyType != 'file') {
        if ($propertyType == 'float') {
            $data = str_replace(",", ".", $propertyData);
        } else {
            $data = $propertyData;
        }

        // set property value
        $result = setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $data, $propertyType, $RSuserID);
    } else {
        // set property value
        $result = setDataPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $propertyName, $propertyData, $propertyType, $RSuserID);
    }

    if ($result != 0) $results['errorProperty' . $err++] = $propertyID . ',' . $result; // error while updating property
}

// retrieve main property ID and value
$mainPropertyID   = getMainPropertyID($itemTypeID, $clientID);
$mainPropertyType = getPropertyType($mainPropertyID, $clientID);

$results['ID'] = $itemID;
$results['mainPropertyValue'] = getItemPropertyValue($itemID, $mainPropertyID, $clientID, $mainPropertyType);

// And write XML Response back to the application
RSReturnArrayResults($results);
