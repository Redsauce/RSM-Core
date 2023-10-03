<?php
// ************************************ //
// Description:
// Creates one or more items (even from different types)
//
// PARAMETERS:
//  RSdata: A text with propertiesIDs and their values encrypted in base64, separated by ":". Each item separated by comma:
//          ID_1:base64(value_1);ID_2:base64(value_2);...;ID_N:base64(value_N) , ID_1:base64(value_1);ID_2:base64(value_2);...;ID_N:base64(value_N)
//          Example to create two items:
//          1077:VEVTVA==;1080:MTExLjIy;1081:MjAxOS0wNi0yMQ==,1077:VEVTVDI=;1080:MTQuMjg=;1081:MjAxOS0wNi0yMg==
// ************************************ //

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

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

    // In order to verify that all properties for each item to create pertain to the same itemType
    $AllPropertiesID = array();

    // Get the itemType with an array of propertyIDs
    foreach ($RSdataRow as $group) {
        $chainValues = explode(":", $group);
        $propertyID = ParsePID($chainValues[0], $clientID);
        $AllPropertiesID[] = $propertyID;
        $value = $chainValues[1];

        if (!is_base64($value)) {
            dieWithError(400, "Input parameters are not base64: ".$value);
        }

        // Only create properties where user has CREATE permission
        if ((RShasTokenPermission($RStoken, $propertyID, "CREATE")) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
            $propertiesID[] = $propertyID;
            $decodedValue = base64_decode($value);

            if (!mb_check_encoding($decodedValue, "UTF-8")) {
                dieWithError(400, "Decoded parameter is not UTF-8 valid");
            }
        }
    }

    // Verify permissions to create this item.
    if (empty($propertiesID)) {
        $results['result'] = 'NOK';
        $results['description'] = 'YOU DONT HAVE PERMISSIONS TO CREATE THIS ITEM';
        error_log('YOU DONT HAVE PERMISSIONS TO CREATE THIS ITEM');
        RSReturnArrayResults($results, false);
    } 

    // Verify all properties pertain to the same item type
    $itemTypeID = getItemTypeIDFromProperties($AllPropertiesID, $clientID);
    if ($itemTypeID == 0) {
        $results['result'] = 'NOK';
        $results['description'] = 'PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE';
        error_log('PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE');
        RSReturnArrayResults($results, false);
    }   
}


// By default response is OK
$results['result'] = 'OK';
$newPropertiesID = array();
foreach ($RSdataSplit as $RSdataRow) {
    $chainValues  = array();
    $propertiesID = array();
    $values       = array();
    
    // Create array with the different values with a double explode
    $RSdataRow = explode(";", $RSdataRow);

    // Get the itemType with an array of propertyIDs
    foreach ($RSdataRow as $propertyID) {
        $chainValues = explode(":", $propertyID);
        $id = ParsePID($chainValues[0], $clientID);
        $value = $chainValues[1];

       // Only prepare properties where user has CREATE permission
        if ((RShasTokenPermission($RStoken, $id, "CREATE")) || (isPropertyVisible($RSuserID, $id, $clientID))) {
            $propertiesID[] = $id;
            $decodedValue = base64_decode($value);
            $newValue = str_replace("&amp;", "&", htmlentities($decodedValue, ENT_COMPAT, "UTF-8"));
            $newValue = str_replace("'", "&#39;", $newValue);
            $values[] = array('ID' => $id, 'value' => $newValue);
        }

    }

    // Create item and verify the result creation
    $newItemID = createItem($clientID, $values);
    if ($newItemID != 0) {
        $newPropertiesID[] = $newItemID;
    } else {
        $results['result'] = 'NOK';
        $results['description'] = 'CREATE FUNCTION RETURNED AN ITEMID 0';
        error_log('CREATE FUNCTION RETURNED AN ITEMID 0');
    }

}

$results['itemID'] = implode(",",$newPropertiesID);

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
?>
