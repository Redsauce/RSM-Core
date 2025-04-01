<?php
//***************************************************************************************
// Description:
//      Get grouped properties + values of the specified item (and itemType)
// REQUEST BODY (JSON OBJECT):
//  Example:
//    {
//      "ID": 5,
//      "itemTypeID": 8
//    }
// The ID parameter is optional.
// The API will return empty results if the item does not exist or is not specified
//***************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');
require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

// Definitions
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

$ID = $requestBody->ID;
$itemTypeID = $requestBody->itemTypeID;

if (!isset($RSuserID)) {
    $RSuserID =  0;
}

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

if ($RSuserID > 0) {
    // We have user credentials
    $results = getPropertiesExtendedForItemAndUser($itemTypeID, $ID, $clientID, $RSuserID);
} elseif ($RStoken != '') {
    // We have token credentials
    $results = getPropertiesExtendedForItemAndToken($itemTypeID, $ID, $RStoken);
} else {
    // We have no credentials
    if ($RSallowDebug) {
        returnJsonMessage(403, "No credentials provided");
    } else {
        returnJsonMessage(403, "");
    }
}
// create the response array
$responseArray = array();
$category = '';

foreach ($results as $item) {
    if (isset($item['category'])) {
        // If the is category, set the category variable
        $category = $item['category'];
        $responseArray[$item['category']] = [];
    } else {
        // If its not a category, add the values to the property
        $property = array(
            'name' => $item['name'],
            'id' => $item['id'],
            'type' => $item['type'],
            'value' => $item['value'] ?: '',
            'realValue' => $item['realValue'] ?: ''
        );
        $responseArray[$category][] = $property;
    }
}
$response = json_encode($responseArray);
returnJsonResponse($response);

    
function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContains($body, "ID");
    checkBodyContains($body, "itemTypeID");
}