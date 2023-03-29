<?php
//***************************************************************************************
// Description:
//    Get one or multiple item/s of the specified itemType with the associated values
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1: 
// {
//     "itemIDs": [571],
//     "typeID": 8
// }
//  EXAMPLE 2: 
// {
//     "itemIDs": [571, 569],
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
function getGivenItems(){
    global $RSallowDebug;
    // verifyBodyContent();
    $requestBody = getRequestBody();
    // DEFINITIONS 
    isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
    isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
    isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

    isset($requestBody->propertyIDs) ? $propertyIDs = $requestBody->propertyIDs : $propertyIDs = "";
    isset($requestBody->filterRules) ? $filterRules = $requestBody->filterRules : $filterRules = "";
    isset($requestBody->extFilterRules) ? $extFilterRules = $requestBody->extFilterRules : $extFilterRules = "";
    isset($requestBody->itemIDs) ? $itemIDs = $requestBody->itemIDs : $itemIDs = "";
    isset($requestBody->typeID) ? $typeID = $requestBody->typeID : $typeID = "";

    $translateIDs = false;
    if (isset($requestBody->$translateIDs)) $translateIDs = true;

    $filterProperties  = array();
    // If filter rules are sent, construct array with specific names for the items
    if ($filterRules != '') {
        foreach ($filterRules as $rule) {
            $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
        }
    }
    // if itemtype not given, obtain and verify 
    if (empty($typeID)) $typeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
    if ($typeID <= 0) {
        if ($RSallowDebug) returnJsonMessage(400, "Incorrect type id");
        else returnJsonMessage(400, "");
    }

    // // Check if user has permissions to read properties of the item, if propertyIDs not providem, obtain them

    if ($propertyIDs == '') {
        $propertyIDs = getClientItemTypePropertiesId($typeID, $clientID);
    }
 //   print_r($propertyIDs);
    // construct string with the itemIDs (if) passed 
    if ($itemIDs != '') {
$itemIDs = implode(",", $itemIDs);
    }
    // ñadir un bucle y quedarse solo con las que tienenpermisos 
    
    // Build array with the visible propertyIds with specific format (for the other functions)
    $visiblePropertyIDs = array();
    foreach ($propertyIDs as $singlePropertyID) {
        if (RShasTokenPermission($RStoken, $singlePropertyID, "READ") || (isPropertyVisible($RSuserID, $singlePropertyID, $clientID))) {
            $visiblePropertyIDs[] = array('ID' => ParsePID($singlePropertyID, $clientID), 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
        }
    }

    // // to use the original functions without changing the php's, we need to transform the following data into an specific format
    $formattedExtFilterRules = "";
    if($extFilterRules !='') {
        foreach($extFilterRules as $singleRule) {
            $formattedExtFilterRules  .=  $singleRule->propertyID . ";" . base64_encode($singleRule->value).";".$singleRule->operation.',';
        }
        $formattedExtFilterRules = substr_replace($formattedExtFilterRules ,"",-1);
    }
    print_r($formattedExtFilterRules);
    $results = getFilteredItemsIDs($typeID, $clientID, $filterProperties, $visiblePropertyIDs, "", $translateIDs, $limit = '', $itemIDs, "AND", 0, true, $formattedExtFilterRules, true);
   print_r($results);
    //And write XML Response back to the application without compression// Return results
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
    if (!isset($body->typeID)) {
        if (!is_int($body->typeID)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'typeID' field must be an integer");
            else returnJsonMessage(400, "");
        }
    }

    //Check that propertyIDs field is an array (just in case it exists)
    if (!isset($body->propertyIDs)) {
        if (!is_array($body->propertyIDs)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'propertyIDs' field must be an array");
            else returnJsonMessage(400, "");
        }
    }

    //Check that itemIDs field is an array (just in case it exists)
    if (!isset($body->itemIDs)) {
        if (!is_array($body->itemIDs)) {
            if ($RSallowDebug) returnJsonMessage(400, "Request body 'itemIDs' field must be an array");
            else returnJsonMessage(400, "");
        }
    }
}
