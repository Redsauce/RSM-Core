<?php
// ****************************************************************************************
//Description:
//  Edits one or more items of the specified typeID with the associated values
//
//  REQUEST BODY (JSON)
// Array with object/s inside, each object must contain
//          - one propertyId and its value (one or more)
//          - id: id of of the item being updated
//  EXAMPLE: 
//      [{
//          "109": "Roja"
//          "id": "0008"
//        },{
//          "id":  "4001"
//          "319": "Peter
//          "320": "Parker"
//        }]
// ****************************************************************************************
updateGivenItems();

function updateGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();

  // Definitions
  $requestBody = getRequestBody();
  $clientID = getClientID();
  $RStoken =  getRStoken();
  $RSuserID =  getRSuserID();

  $responseArray = array();
  foreach ($requestBody as $item) {
    $combinedArray = array();
    $propertiesID = array();
    //Iterate through every propertyID of the items to check if they are incongruent
    foreach ($item as $propertyID => $propertyValue) {
      if ($propertyID != "id" && $propertyID != "ID") $propertiesID[] = ParsePID($propertyID, $clientID);
    }
    $typeIDID = getItemTypeIDFromProperties($propertiesID, $clientID);
    $hasAllPermissions = checkTokenHasAllPermissions($RStoken, $RSuserID, $clientID, $propertiesID);
    $itemID = $item->ID;

    if ($typeIDID == 0) {
      $combinedArray['itemID'] = $itemID;
      $combinedArray['error'] = "Not Updated (Incongruent properties)";
    } else if (!$hasAllPermissions) {
      $combinedArray['itemID'] = $itemID;
      $combinedArray['error'] = "Not Updated (At least 1 property has no WRITE permissions or its not visible)";
    } else {
      $combinedArray['typeID'] = intval($typeIDID);
      $combinedArray['itemID'] = $itemID;
      foreach ($item as $propertyID => $propertyValue) {
        if ($propertyID != "ID") {
          $id = ParsePID($propertyID, $clientID);
          $propertyType = getPropertyType($id, $clientID);
          if (($propertyType == 'file') || ($propertyType == 'image')) {
            //TODO - ASK ON HOW UPDATE FILE/IMAGE SHOULD WORK AND WHY ":" IS NEEDED
          } else {
            if (!mb_check_encoding($propertyValue, "UTF-8")) {
              if ($RSallowDebug) returnJsonMessage(400, "Decoded parameter:" . $propertyValue . " is not UTF-8 valid");
              else returnJsonMessage(400, "");
            }
            $parsedValue = replaceUtf8Characters($propertyValue);
            $result = setPropertyValueByID($id, $typeIDID, $itemID, $clientID, $parsedValue, $propertyType);
          }
          // Result = 0 is a successful response
          if ($result != 0) {
            $combinedArray[$propertyID] = 'Not Updated (' . $result . ')';
            continue;
          } else $combinedArray[$propertyID] = 'Updated';
        }
      }
    }
    array_push($responseArray, $combinedArray);
  }

  $response = json_encode($responseArray);

  if ($RSallowDebug and $response != "[]") {
    returnJsonResponse($response);
  } else returnJsonMessage(200, "");
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  global $RSallowDebug;

  $body = getRequestBody();
  if (!is_array($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be an array '[]'");
    else returnJsonMessage(400, "");
  }
  foreach ($body as $item) {

    //Check JSON objects
    if (!is_object($item)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body array elements must be JSON objects '{}'");
      else returnJsonMessage(400, "");
    }

    //Check field 'ID' exists
    if (!isset($item->ID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body items must contain field 'ID'");
      else returnJsonMessage(400, "");
    }

    //Check that ID field is an integer
    if (!is_int($item->ID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'ID' field must be an integer");
      else returnJsonMessage(400, "");
    }
  }
}

function checkTokenHasAllPermissions($RStoken, $RSuserID, $clientID, $propertiesID)
{
  foreach ($propertiesID as $propertyID) {
    if (RShasTokenPermission($RStoken, $propertyID, "WRITE") || isPropertyVisible($RSuserID, $propertyID, $clientID)) {
      continue;
    }
    return false;
  }
  return true;
}
