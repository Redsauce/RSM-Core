<?php
//***************************************************************************************
// Description:
//    Get one or multiple item/s of the specified itemType with the associated values
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1: 
// {
//     "itemIds": [571],
//     "typeId": 8
// }
//  EXAMPLE 2: 
// {
//     "itemIds": [571, 569],
//     "propertiesIds": [58,59],
//     "orderBy": 58,
// }
//  EXAMPLE 3: 
// {
//     "propertiesIds": [59],
//     "filtersRules": 
//      [
//          {
//              “propertyId”: 58,
//              “value”: “Sergio”,
//              “operation”: “=”
//          }.
//          {
//              “propertyId”: 59,
//              “value”: “Santamaria”,
//              “operation”: “<>”
//          }
//      ],
//      "filterJoining": "AND"
// }
//***************************************************************************************

getGivenItems();
function getGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();

  
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
