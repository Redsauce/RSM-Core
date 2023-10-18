<?php
//***************************************************************************************
// Description:
//    Get one, multiple or all item types and its associated propertyIDS + NAME
//
//  EXAMPLE 1:
//    -Use the endpoint without any param or body to obtain all of them
//
//  EXAMPLE 2
//    - Add a query param with the specific itemtypeIDs all separated by comma
//            ?ID=6,7,8
//***************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';

$clientID = getClientID();
$RStoken = getRStoken();
$RSuserID = getRSuserID();


// Verify if id's are sent in the request.
if (!isset($_GET['ID']) && empty($_GET['ID'])) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), 'ID');
} else {
    $itemTypeIDs = explode(',', $_GET['ID']);
}

// Initialize an empty array to store the response data.
$responseArray = array();

foreach ($itemTypeIDs as $itemTypeID) {
    // Initialize an empty array for each item type.
    $combinedArray = array();
    
    // Get the name of the item type
    $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);

    if ($itemTypeIDName != '') {
        $combinedArray['itemTypeID'] = $itemTypeID;
        $combinedArray['name'] = $itemTypeIDName;
        
        // Get the properties of the item type
        $properties = getClientItemTypeProperties($itemTypeID, $clientID);
        $propertiesArray = array();

        foreach ($properties as $property) {
            // Check if user has read permission of the property
            if ((RShasTokenPermission($RStoken, $property['id'], 'READ')) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
                $propertiesArray[$property['id']] = $property['name'];
            }
        }
        // Check if there are properties to add to the combined array.
        if (!empty($propertiesArray)) {
            $combinedArray['properties'] = $propertiesArray;
        }
        array_push($responseArray, $combinedArray);
    }
}

if (!empty($responseArray)) {
    returnJsonResponse(json_encode($responseArray));
} else {
    $RSallowDebug ? returnJsonMessage(404, 'No  itemTypeIDs were found') : returnJsonMessage(404, '');
}
