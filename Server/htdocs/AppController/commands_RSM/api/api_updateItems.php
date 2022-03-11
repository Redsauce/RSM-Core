<?php

// ****************************************************************************************
//Description:
//  Edits an item of the specified itemType with the associated values
//
//  PARAMETERS:
//        RSdata: jsonstring encoded in base 64.
//                Example: eyI4NyI6eyIxNTciOiJTdGRyIiwiMTU4IjoiQ2FudC4ifSwiNDMiOnsiMTU3IjoiU3RkciJ9fQ== to represent
//                         {"87":{"157":"Stdr","158":"Cant."},"43":{"157":"Stdr"}}
//  The json is {itemID1:{propertyID11:value11,propertyID12:value12...}, itemID2:{propertyID21:value21,propertyID22:value22...}}
//  where itemID1, itemID2 are the different itemIDs to modify, an pairs of property/value to modify
// ****************************************************************************************
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// definitions
isset($GLOBALS['RS_POST']['clientID'])  ? $clientID  = $GLOBALS['RS_POST']['clientID' ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSdata'  ])  ? $RSdata    = $GLOBALS['RS_POST']['RSdata'   ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken' ])  ? $RStoken   = $GLOBALS['RS_POST']['RStoken'  ] : $RStoken  = '';

$itemID      = array();
$value       = array();
$itemTypeID  = array();
$allItemRows = array();
$results     = array();
// initially it will be right
$results['result'] = 'OK';

// Build a JSON with the given RSdata
$RSdataToJSON = json_decode(base64_decode($RSdata),true);

// For each item to update, verify all given properties are compatible
foreach ($RSdataToJSON as $itemIDkey => $itemRow) {

    // Build new array of properties for each item to update
    $propertiesID = array();

    // Store the itemID
    array_push($itemID,$itemIDkey);

    // Store all data for each itemID into an array (one array element per itemID)
    array_push($allItemRows,$itemRow);
    foreach($itemRow as $propertyIDkey => $value) {

        // Fill the array with the different properties involved in the update of this row
        array_push($propertiesID,$propertyIDkey);
    }

    // Store the different calculated itemTypeIDs into an array
    array_push($itemTypeID,getItemTypeIDFromProperties($propertiesID, $clientID));

    // Verify if the last element of $itemTypeID is right recovered
    if ($itemTypeID[count($itemTypeID)-1] == 0) {
        $results['result']      = 'NOK';
        $results['description'] = 'INCONGRUENT PROPERTIES FOR THIS CLIENT';
        error_log('INCONGRUENT PROPERTIES FOR THIS CLIENT');
        break;
    }
}

// All items have congruent properties to modify
if ($results['result'] != 'NOK') {
    $i = 0;

    // Every item must be updated
    foreach ($itemID as $item){

        // Every property of each item must be updated
        foreach($allItemRows[$i] as $propertyIDkey => $value) {

            array_push($propertiesID,$propertyIDkey);
            $id = ParsePID($propertyIDkey, $clientID);

            // Only update properties that user has WRITE permissions
            if (RShasTokenPermission($RStoken, $id, "WRITE") || isPropertyVisible($RSuserID, $id, $clientID)) {
                $propertyType = getPropertyType($id, $clientID);

                if (($propertyType == 'file') || ($propertyType == 'image')) {
                    //$pieces = explode(":", base64_decode($value));
                    $pieces = explode(":", $value);

                    if (count($pieces) == 1) {
                        $name = "";
                        $value = $pieces[0];
                    } else {
                        $name = $pieces[0];
                        $value = $pieces[1];
                    }

                    if ($value == "") {
                        deleteItemPropertyValue($itemTypeID[$i], $item, $id, $clientID, $propertyType);
                    } else {
                        $result = setDataPropertyValueByID($id, $itemTypeID[$i], $item, $clientID, $name, $value, $propertyType, $RSuserID);
                    }
                } else {

                    if (!mb_check_encoding($value, "UTF-8")) {
                        dieWithError(400, "Decoded parameter is not UTF-8 valid");
                    }
                    $newValue = str_replace("&amp;", "&", htmlentities($value, ENT_COMPAT, "UTF-8"));
                    $newValue = str_replace("'", "&#39;", $newValue);
                    $result = setPropertyValueByID($id, $itemTypeID[$i], $item, $clientID, $newValue, $propertyType);
                }

                $results['result'] = 'OK';

                // Result = 0 is a successful response
                if ($result != 0) {
                    $results['result'] = "NOK";
                    $results['description'] = "CODE ERROR " . $result;
                    $results['propertyID'] = $propertyIDkey . " (PID: " . $id . ")";
                    continue;
                }

            }
        }

        $i = $i + 1;
    }
}

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
?>
