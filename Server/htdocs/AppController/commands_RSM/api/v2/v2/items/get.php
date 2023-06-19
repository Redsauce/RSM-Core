<?php
//***************************************************************************************
// Description:
//    Get one or multiple item/s of the specified itemType with the associated values
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
// {
//     "IDs": [571],
//     "itemTypeID": 8
// }
//  EXAMPLE 2:
// {
//     "IDs": [571, 569],
//     "propertiesIDs": [58,59]
// }
//  EXAMPLE 3:
// {
//     "propertiesIDs": [59],
//     "filtersRules":
//      [
//          {
//              "propertyID": 58,
//              "value": "Sergio",
//              "operation": "="
//          }.
//          {
//              "propertyID": 59,
//              "value": "Santamaria",
//              "operation": "<>"
//          }
//      ]
// }
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

$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

//Params
$propertyIDs = $requestBody->propertyIDs;
$filterRules = $requestBody->filterRules;
$extFilterRules = $requestBody->extFilterRules;
$IDs = $requestBody->IDs;
$itemTypeID = $requestBody->itemTypeID;

//includeCategories filter
$includeCategories = false;
if (isset($requestBody->includeCategories) && $requestBody->includeCategories) {
    $includeCategories = true;
}

//translateIDs
$translateIDs = false;
if (isset($requestBody->translateIDs)) {
    $translateIDs = true;
}

//itemTypeID
if ($itemTypeID == '') {
    $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
}
if ($itemTypeID <= 0) {
    if ($RSallowDebug) {
        returnJsonMessage(400, "Invalid itemTypeID: " . $itemTypeID);
    } else {
        returnJsonMessage(400, "");
    }
}

//propertyIDs
if ($propertyIDs == '') {
    $propertyIDs = getClientItemTypePropertiesId($itemTypeID, $clientID);
}

//IDs
if ($IDs != '') {
    $IDs = implode(",", $IDs);
}

// Build an array with the filterRules
$filterProperties  = array();
if ($filterRules != '') {
    foreach ($filterRules as $rule) {
        $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
    }
}

// Build array with the visible propertyIds (the ones we have permissions)
$visiblePropertyIDs = array();
foreach ($propertyIDs as $singlePropertyID) {
    if (RShasTokenPermission($RStoken, $singlePropertyID, "READ") || (isPropertyVisible($RSuserID, $singlePropertyID, $clientID))) {
        $visiblePropertyIDs[] = array('ID' => ParsePID($singlePropertyID, $clientID), 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
    }
}

// Build a string with the extFilterRules
$formattedExtFilterRules = "";
if ($extFilterRules != '') {
    foreach ($extFilterRules as $singleRule) {
        // To use getFilteredItemsIDs function without changing the original php's, we need to transform the following data into an specific format (base64)
        $formattedExtFilterRules  .=  $singleRule->propertyID . ";" . base64_encode($singleRule->value) . ";" . $singleRule->operation . ',';
    }
    $formattedExtFilterRules = substr_replace($formattedExtFilterRules, "", -1);
}

//GET THE ITEMS
$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, "", $translateIDs, $limit = '', $IDs, "AND", 0, true, $formattedExtFilterRules, true);
$responseArray = array();

// To construct the response, we have to verify if the includecategories filter is true
if ($includeCategories) {
    // obtain all the corresponding properties and its categories
    $categorizedProperties = getPropertiesExtendedForToken($itemTypeID, $RStoken, $visiblePropertyIDs);
    // parse all the different items of the original response
    foreach ($itemsArray as $item) {
        $combinedArray = array();
        $combinedArray['ID'] = $item['ID'];
        // loop through the categories and save its values
        foreach ($categorizedProperties as $property) {
            $category = $property['Category'];
            $propertyID = $property['propertyID'];
            // save the values in the new array, with its corresponding categories
            if (isset($item[$propertyID])) {
                $combinedArray[$category][$propertyID] = $item[$propertyID];
            } else {
                $combinedArray[$category][$propertyID] = '';
            }
        }
        // construct the response array by pushing each one of the items
        array_push($responseArray, $combinedArray);
    }

    // convert to json
    $response = json_encode($responseArray);
} else {
    //  Parse itemsArray into a JSON.
    foreach ($itemsArray as $item) {
        $combinedArray = array();
        foreach ($item as $propertyKey => $propertyValue) {
            $combinedArray[$propertyKey] = $propertyValue;
        }
        array_push($responseArray, $combinedArray);
    }

    $response = json_encode($responseArray);
}
if ($response != "[]") {
    returnJsonResponse($response);
} else {
    returnJsonMessage(404, "No items were found");
}


// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContainsAtLeastOne($body, "itemTypeID", "propertyIDs");
    checkIsInteger($body->itemTypeID);
    checkIsArray($body->propertyIDs);
    checkIsArray($body->IDs);
}