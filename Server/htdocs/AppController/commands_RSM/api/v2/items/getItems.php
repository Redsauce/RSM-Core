<?php
//***************************************************************************************
// Description:
//    Get one or multiple item/s of the specified itemType with the associated values
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1: 
// {
//     "IDs": [571],
//     "typeID": 8
// }
//  EXAMPLE 2: 
// {
//     "IDs": [571, 569],
//     "propertiesIDs": [58,59],
//     "orderBy": 58,
// }
//  EXAMPLE 3: 
// {
//     "propertiesIDs": [59],
//     "filtersRules": 
//      [
//          {
//              “propertyID”: 58,
//              “value”: “Sergio”,
//              “operation”: “=”
//          }.
//          {
//              “propertyID”: 59,
//              “value”: “Santamaria”,
//              “operation”: “<>”
//          }
//      ],
//      "filterJoining": "AND",
//      "extFilterRules": { 
//                     "propertyID": 43,
//                      "value": "adsdad"
//                       "condition": "algo"       
//              }
// }
//***************************************************************************************
// TODO check why routes are not relative
require_once "../../utilities/RSMfiltersManagement.php";
require_once "../../utilities/RSMlistsManagement.php";

getGivenItems();
function getGivenItems()
{
    // Definitions
    global $RSallowDebug;
    verifyBodyContent();
    $requestBody = getRequestBody();
    $clientID = getClientID();
    $RStoken =  getRStoken();
    $RSuserID =  getRSuserID();
    //Params
    $propertyIDs = $requestBody->propertyIDs;
    $filterRules = $requestBody->filterRules;
    $extFilterRules = $requestBody->extFilterRules;
    $IDs = $requestBody->IDs;
    $typeID = $requestBody->typeID;

    //translateIDs
    $translateIDs = false;
    if (isset($requestBody->$translateIDs)) $translateIDs = true;

    //typeID
    if ($typeID == '') $typeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
    if ($typeID <= 0) {
        if ($RSallowDebug) returnJsonMessage(400, "Invalid typeID: " . $typeID);
        else returnJsonMessage(400, "");
    }

    //propertyIDs
    if ($propertyIDs == '') $propertyIDs = getClientItemTypePropertiesId($typeID, $clientID);

    //IDs
    if ($IDs != '') $IDs = implode(",", $IDs);

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
    $itemsArray = getFilteredItemsIDs($typeID, $clientID, $filterProperties, $visiblePropertyIDs, "", $translateIDs, $limit = '', $IDs, "AND", 0, true, $formattedExtFilterRules, true);

    //Parse itemsArray into a JSON.
    $response = "[";
    foreach ($itemsArray as $item) {
        $response .= "{";
        foreach ($item as $propertyKey => $propertyValue) {
            $response .= '"' . $propertyKey . '": "' . $propertyValue . '",';
        }
        $response = rtrim($response, ",") . '},';
    }
    $response = rtrim($response, ",") . ']';

    //Return response
    if ($response != "[]") {
        header('Content-Type: application/json', true, 200);
        Header("Content-Length: " . strlen($response));
        echo $response;
        die();
    } else returnJsonMessage(404, "No items were found");
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
    global $RSallowDebug;
    $body = getRequestBody();

    //Check that request body is an object
    if (!is_object($body)) {
        if ($RSallowDebug) returnJsonMessage(400, "Request body must be a JSON object '{}'");
        else returnJsonMessage(400, "");
    }

    //Check that body contains at least "typeID" or "propertyIDs"
    if (!isset($body->typeID) and !isset($body->propertyIDs)) {
        if ($RSallowDebug) returnJsonMessage(400, "Request body must contain at least field 'typeID' or field 'propertyIDs'");
        else returnJsonMessage(400, "");
    }

    //Check that typeID field is an integer (just in case it exists)
    if (isset($body->typeID)) {
        if (!is_int($body->typeID)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'typeID' field must be an integer");
            else returnJsonMessage(400, "");
        }
    }

    //Check that propertyIDs field is an array (just in case it exists)
    if (isset($body->propertyIDs)) {
        if (!is_array($body->propertyIDs)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'propertyIDs' field must be an array");
            else returnJsonMessage(400, "");
        }
    }

    //Check that IDs field is an array (just in case it exists)
    if (isset($body->IDs)) {
        if (!is_array($body->IDs)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'IDs' field must be an array");
            else returnJsonMessage(400, "");
        }
    }
}
