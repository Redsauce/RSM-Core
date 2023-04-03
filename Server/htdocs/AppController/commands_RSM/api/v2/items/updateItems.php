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

  $response = "[";
  foreach ($requestBody as $item) {
    $propertiesID = array();
    //Iterate through every propertyID of the items to check if they are incongruent
    foreach ($item as $propertyID => $propertyValue) {
      if ($propertyID != "id") $propertiesID[] = ParsePID($propertyID, $clientID);
    }
    $typeIDID = getItemTypeIDFromProperties($propertiesID, $clientID);
    $ID = $item->ID;

    if ($typeIDID != 0) {
      $response .= '{ "typeID": ' . $typeIDID . ', "ID": ' . $ID . ',';

      foreach ($item as $propertyID => $propertyValue) {
        if ($propertyID != "ID") {
          $id = ParsePID($propertyID, $clientID);

          // Only update properties that user has WRITE permissions
          if (RShasTokenPermission($RStoken, $id, "WRITE") || isPropertyVisible($RSuserID, $id, $clientID)) {
            $propertyType = getPropertyType($id, $clientID);
            if (($propertyType == 'file') || ($propertyType == 'image')) {
              //TODO - ASK ON HOW UPDATE FILE/IMAGE SHOULD WORK AND WHY ":" IS NEEDED
            } else {
              if (!mb_check_encoding($propertyValue, "UTF-8")) {
                if ($RSallowDebug) returnJsonMessage(400, "Decoded parameter:" . $propertyValue . " is not UTF-8 valid");
                else returnJsonMessage(400, "");
              }
              $parsedValue = replaceUtf8Characters($propertyValue);
              $result = setPropertyValueByID($id, $typeIDID, $ID, $clientID, $parsedValue, $propertyType);
            }
            // Result = 0 is a successful response
            if ($result != 0) {
              $response .= '"' . $propertyID . '": "Not Updated (' . $result . ')",';
              continue;
            } else $response .= '"' . $propertyID . '": "Updated",';
          } else {
            $response .= '"' . $propertyID . '": "Not Updated (No WRITE permissions or property not visible)",';
          }
        }
      }
      $response = rtrim($response, ",") . '},';
    } else {
      $response .= '{ "ID": ' . $ID . ', "error": "Not Updated (Incongruent properties)"}';
    }
  }
  $response = rtrim($response, ",") . ']';

  if ($RSallowDebug and $response != "[]") {
    header('Content-Type: application/json', true, 200);
    Header("Content-Length: " . strlen($response));
    echo $response;
    die();
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
