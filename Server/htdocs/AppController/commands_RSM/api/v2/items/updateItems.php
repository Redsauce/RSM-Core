<?php

updateGivenItems();

function updateGivenItems()
{
  verifyBodyContent();
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

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
                dieWithError(400, "Decoded parameter is not UTF-8 valid");
              }
              $parsedValue = prepareValues($propertyValue);
              $result = setPropertyValueByID($id, $itemTypeID, $itemID, $clientID, $parsedValue, $propertyType);
            }
            // Result = 0 is a successful response
            if ($result != 0) {
              $results['result'] = "NOK";
              $results['description'] = "CODE ERROR " . $result;
              $results['propertyID'] = $propertyID . " (PID: " . $id . ")";
              continue;
            }
          }
        }
      }
    } else {
      //TODO: RETURN JSON ERROR 'INCONGRUENT PROPERTIES FOR THIS CLIENT'
    }
  }
  //TODO: Return VALID JSON 
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  $body = getRequestBody();
  if (!is_array($body)) dieWithError(400);
  foreach ($body as $item) {
    if (!is_object($item)) dieWithError(400);
    if (!isset($item->id)) dieWithError(400);
  }
}
