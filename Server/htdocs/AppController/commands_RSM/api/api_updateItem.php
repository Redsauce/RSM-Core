<?php
// ****************************************************************************************
//Description :
//    Edits an item of the specified itemType with the associated values
//
//  PARAMETERS:
//        itemTypeID: ID of the itemType to edit
//  propertiesValues: A text with propertiesIDs and their new values with this sintaxis:
//                    ID_1:base64(value_1);ID_2:base64(value_2);...;ID_N:base64(value_N)
// ****************************************************************************************
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// definitions
isset($GLOBALS[$cstRS_POST][$cstClientID]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['RSitemID']) ? $RSitemID = $GLOBALS[$cstRS_POST]['RSitemID'] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['RSdata'  ]) ? $RSdata   = $GLOBALS[$cstRS_POST]['RSdata'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken  = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken  = '';

$values      = array();
$chainValues = array();
$results     = array();

// Create array with the different values with a double explode
$RSdata = explode(";", $RSdata);

// Get the itemType with an array of propertyIDs
// Construct the array to use the function getItemTypeIDFromProperties
foreach ($RSdata as $propertyID) {
    $chainValues = explode(":", $propertyID);
    $propertiesID[] = ParsePID($chainValues[0], $clientID);
}

$itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);

if ($itemTypeID != 0 && !RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $RSitemID)) {
    $results['result'] = 'NOK';
    $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THIS ITEM';
    RSReturnArrayResults($results, false);
}

// Correct itemTypeID
if ($itemTypeID != 0) {
    // For every property must be done one update
    foreach ($RSdata as $propertyValue) {
        $chainValues = explode(":", $propertyValue);
        $id = ParsePID($chainValues[0], $clientID);
        // Only update properties that user has WRITE permissions
        if (RShasTokenPermission($RStoken, $id, "WRITE") || isPropertyVisible($RSuserID, $id, $clientID)) {
            $propertyType = getPropertyType($id, $clientID);

            if (($propertyType == 'file') || ($propertyType == 'image')) {
                $pieces = explode(":", base64_decode($chainValues[1]));

                if (count($pieces) == 1) {
                    $name = "";
                    $value = $pieces[0];
                } else {
                    $name = $pieces[0];
                    $value = $pieces[1];
                }

                if ($value == "") {
                    deleteItemPropertyValue($itemTypeID, $RSitemID, $id, $clientID, $propertyType);
                } else {
                    $result = setDataPropertyValueByID($id, $itemTypeID, $RSitemID, $clientID, $name, $value, $propertyType, $RSuserID);
                }
            } else {

                $value = $chainValues[1];
                if (!isBase64($value)) {
                    dieWithError(400, "Input parameters are not base64");
                }

                $decodedValue = base64_decode($value);
                if (!mb_check_encoding($decodedValue, "UTF-8")) {
                    dieWithError(400, "Decoded parameter is not UTF-8 valid");
                }
                $newValue = str_replace("&amp;", "&", htmlentities($decodedValue, ENT_COMPAT, "UTF-8"));
                $newValue = str_replace("'", "&#39;", $newValue);
                $result = setPropertyValueByID($id, $itemTypeID, $RSitemID, $clientID, $newValue, $propertyType);
            }

            $results['result'] = 'OK';

            // Result = 0 is a successful response
            if ($result != 0) {
                $results['result'] = "NOK";
                $results['description'] = "CODE ERROR " . $result;
                $results['propertyID'] = $chainValues[0] . " (PID: " . $id . ")";
            }
        }
    }
} else {
    $results['result'] = 'NOK';
    $results['description'] = 'INCONGRUENT PROPERTIES FOR THIS CLIENT';
}

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);

