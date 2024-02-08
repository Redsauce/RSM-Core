<?php
//***************************************************************************************
// Description:
//    Get one, multiple or all item types and its associated propertyIDS + NAME
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
//    -Use the endpoint without any param or body to obtain all of them
//
//  EXAMPLE 2
//    - Add a query param with the specific itemtypeIDs
//            ?ID=6,7,8
//***************************************************************************************
require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

// Definitions
$clientID = getClientID();
$RStoken = getRStoken();
$RSuserID = getRSuserID();

// Check if 'ID' is not set or is empty in the $_GET parameters

if (!isset($_GET['ID']) && empty($_GET['ID'])) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), "ID");
} else {
    $itemTypeIDs = explode(',', $_GET['ID']);
}
$responseArray = array();

foreach ($itemTypeIDs as $itemTypeID) {
    $combinedArray = array();
    
    // Get the name of the ItemTypeID
    $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);
    
    if ($itemTypeIDName != "") {
        $combinedArray['itemTypeID'] = $itemTypeID;
        $combinedArray['name'] = $itemTypeIDName;

        // Get properties associated with the current ItemTypeID
        $properties = getClientItemTypeProperties($itemTypeID, $clientID);
        $propertiesArray = array();

        // Loop through each property
        foreach ($properties as $property) {
            // Check if user has read permission of the property
            if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
                $propertiesArray[$property['id']] = $property['name'];
            }
        }
        if (!empty($propertiesArray)) {
            $combinedArray['properties'] = $propertiesArray;
        }
        array_push($responseArray, $combinedArray);
    }
}

if (!empty($responseArray)) {
    returnJsonResponse(json_encode($responseArray));
} else {
    returnJsonMessage(200, '{}');
}
