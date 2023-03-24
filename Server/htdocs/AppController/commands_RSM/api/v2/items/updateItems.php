<?php

updateGivenItems();

function updateGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

  $response = "";
  foreach ($requestBody as $item) {
    $propertiesID = array();
    //Iterate through every propertyID of the items to check if they are incongruent
    foreach ($item as $propertyID => $propertyValue) {
      if ($propertyID != "id") $propertiesID[] = ParsePID($propertyID, $clientID);
    }
    $itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);

    if ($itemTypeID != 0) {
      $itemID = $item->id;
      foreach ($item as $propertyID => $propertyValue) {
        if ($propertyID != "id") {
          $id = ParsePID($propertyID, $clientID);

          if (RShasTokenPermission($RStoken, $id, "WRITE") || isPropertyVisible($RSuserID, $id, $clientID)) {
            $propertyType = getPropertyType($id, $clientID);
            if (($propertyType == 'file') || ($propertyType == 'image')) {
              //TODO - ASK ON HOW UPDATE FILE/IMAGE SHOULD WORK AND WHY ":" IS NEEDED
              $pieces = explode(":", $propertyValue);
              if (count($pieces) == 1) {
                $name = "";
                $value = $pieces[0];
              } else {
                $name = $pieces[0];
                $value = $pieces[1];
              }
              if ($value == "") {
                deleteItemPropertyValue($itemTypeID, $itemID, $id, $clientID, $propertyType);
              } else {
                $result = setDataPropertyValueByID($id, $itemTypeID, $itemID, $clientID, $name, $value, $propertyType, $RSuserID);
              }
            } else {
              if (!mb_check_encoding($propertyValue, "UTF-8")) {
                if ($RSallowDebug) returnJsonMessage(400, "Decoded parameter is not UTF-8 valid");
                else returnJsonMessage(400, "");
              }
              $parsedValue = prepareValues($propertyValue);
              $result = setPropertyValueByID($id, $itemTypeID, $itemID, $clientID, $parsedValue, $propertyType);
              $response .= "[itemTypeID: ".$itemTypeID.", itemID: ".$itemID.", properyID: ".$id."],";
            }
            // Result = 0 is a successful response
            if ($result != 0) {
              $response .= "[CODE ERROR ".$result.", propertyID ".$propertyID." (PID: ".$id.")],";
              continue;
            }
          }
        }
      }
    } else {
      if ($RSallowDebug) returnJsonMessage(403, "INCONGRUENT PROPERTIES FOR THIS CLIENT");
      else returnJsonMessage(400, "");
    }
  }
  returnJsonMessage(200, "Items updated: ".rtrim($response,","));
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  global $RSallowDebug;

  $body = getRequestBody();
  if (!is_array($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be an array");
    else returnJsonMessage(400, "");
  }
  foreach ($body as $item) {
    if (!is_object($item)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body items must be objects '{}'");
      else returnJsonMessage(400, "");
  }
    if (!isset($item->id)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body items must contain field 'id'");
      else returnJsonMessage(400, "");
    }
  }
}
