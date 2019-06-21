<?php
// ************************************ //
// Description:
// Creates an item of the specified itemType with the associated values
//
// PARAMETERS:
//  RSdata: A text with propertiesIDs and their values with this syntax:
//          ID_1:base64(value_1);ID_2:base64(value_2);...;ID_N:base64(value_N) , ID_1:base64(value_1);ID_2:base64(value_2);...;ID_N:base64(value_N)
//          Example to create two items:
//          1077:VEVTVA==;1080:MTExLjIy;1081:MjAxOS0wNi0yMQ==,1077:VEVTVDI=;1080:MTQuMjg=;1081:MjAxOS0wNi0yMg==
// ************************************ //

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

$RSallowUncompressed = true;

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSdata'  ]) ? $RSdata   = $GLOBALS['RS_POST']['RSdata'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken' ]) ? $RStoken  = $GLOBALS['RS_POST']['RStoken' ] : $Rstoken = "";

$chainValues  = array();
$propertiesID = array();
$RSdataSplit = explode(",", $RSdata);

// Verify all possible errors related with permissions, base64 and UTF-8 encoding.
foreach ($RSdataSplit as $RSdataRow) {

    // Create array with the different values with a double explode
    $RSdataRow = explode(";", $RSdataRow);

    // Get the itemType with an array of propertyIDs
    foreach ($RSdataRow as $propertyID) {
        $chainValues = explode(":", $propertyID);
        $id = ParsePID($chainValues[0], $clientID);
        $value = $chainValues[1];

        if (!is_base64($value)) {
            dieWithError(400, "Input parameters are not base64: ".$value);
        }

        // Only create properties where user has CREATE permission
        if ((RShasTokenPermission($RStoken, $id, "CREATE")) || (isPropertyVisible($RSuserID, $id, $clientID))) {
            $propertiesID[] = $id;
            $decodedValue = base64_decode($value);

            if (!mb_check_encoding($decodedValue, "UTF-8")) {
                dieWithError(400, "Decoded parameter is not UTF-8 valid");
            }
        }
    }

    if (empty($propertiesID)) {
        $results['result'] = 'NOK';
        $results['description'] = 'YOU DONT HAVE PERMISSIONS TO CREATE THIS ITEM';
        error_log('YOU DONT HAVE PERMISSIONS TO CREATE THIS ITEM');
        RSReturnArrayResults($results, false);
    } 
}

$newPropertiesID = array();
// By default response is OK
$results['result'] = 'OK';
foreach ($RSdataSplit as $RSdataRow) {
    $chainValues  = array();
    $propertiesID = array();
    $values       = array();
    // Create array with the different values with a double explode
    $RSdataRow = explode(";", $RSdataRow);

    // Get the itemType with an array of propertyIDs
    // Construct the array to use the function getItemTypeIDFromProperties
    foreach ($RSdataRow as $propertyID) {
        $chainValues = explode(":", $propertyID);
        $id = ParsePID($chainValues[0], $clientID);
        $value = $chainValues[1];

       // Only create properties where user has CREATE permission
        if ((RShasTokenPermission($RStoken, $id, "CREATE")) || (isPropertyVisible($RSuserID, $id, $clientID))) {
            $propertiesID[] = $id;
            $decodedValue = base64_decode($value);
            $values[] = array('ID' => $id, 'value' => $decodedValue);
        }

    }

    // Get Item Type from Properties
    $itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);

    // Error control
    if ($itemTypeID != 0) {
        $newItemID = createItem($clientID, $values);
        if ($newItemID != 0) {
            $newPropertiesID[] = $newItemID;
        } else {
            $results['result'] = 'NOK';
            $results['description'] = 'CREATE FUNCTION RETURNED AN ITEMID 0';
            error_log('CREATE FUNCTION RETURNED AN ITEMID 0');
        }
    } else {
        $results['result'] = 'NOK';
        $results['description'] = 'PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE';
        error_log('PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE');
    }

}

$results['itemID'] = implode($newPropertiesID,",");

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
?>