<?php
//***************************************************************************************
// Description:
//    Get one, multiple or all item types and its associated propertyIDS + NAME
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
//    - Use the endpoint without body to obtain all of them
//
//  EXAMPLE 2:
//    {
//      "IDs": ["6","7","8"]
//    }
//***************************************************************************************
require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

// Verify if the request has a body and validate its content
$contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength !== 0) {
    $requestBody = getRequestBody();
    verifyBodyContent($requestBody);
}

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

// Check if there is a request body sent
if (!isset($requestBody) || empty($requestBody)) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), "ID");
} else {
    $itemTypeIDs = $requestBody->IDs;
}

$responseArray = array();

foreach ($itemTypeIDs as $itemTypeID) {
    $combinedArray = array();

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

    // Only sent them when the user has permissions to see properties.
    if (!empty($propertiesArray)) {
        // Get the name of the ItemTypeID
        $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);
        // Get the icon of the ItemTypeID
        $itemTypeIDIcon = getClientItemTypeIcon($itemTypeID, $clientID);

        // Add the itemTypeID and the name to the array.
        $combinedArray['itemTypeID'] = $itemTypeID;
        $combinedArray['name'] = $itemTypeIDName;

        //Add the properties to the array
        $combinedArray['properties'] = $propertiesArray;
        $combinedArray['icon'] = base64_encode(hex2bin($itemTypeIDIcon));

        array_push($responseArray, $combinedArray);
    }
}

if (!empty($responseArray)) {
    returnJsonResponse(json_encode($responseArray));
} else {
    returnJsonMessage(200, '{}');
}

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkIsArray($body->IDs);
}
