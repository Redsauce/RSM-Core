<?php
// ****************************************************************************************
//Description:
//  Creates one or more items (even from different types)
//
//  REQUEST BODY (JSON)
//  Array with object/s inside, each object must contain at least
//          - one propertyID and its value
//  EXAMPLE: 
//      [{
//          "85": "Mesa"
//          "86": "individual"
//        },{
//          "45": "Avengers"
//          "46": "vose"
//       }]
// ****************************************************************************************
createGivenItems();
function createGivenItems()
{
  global $RSallowDebug;

  $requestBody = getRequestBody();
  verifyBodyContent($requestBody);

  // Definitions
  $clientID = getClientID();
  $RStoken =  getRStoken();
  $RSuserID =  getRSuserID();

  $responseArray = array();

  foreach ($requestBody as $item) {
    $values = array();
    $propertiesCount = 0;
    $correctProperties = array();

    foreach ($item as $propertyID => $propertyValue) {
      // count how many properties were sent
      ++$propertiesCount;
      // Only prepare properties where user has CREATE permission
      if ((RShasTokenPermission($RStoken, $propertyID, "CREATE")) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
        $correctProperties[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
        if ($RSallowDebug) $values[$propertyID] = 'Permissions OK';
      } else {
        if ($RSallowDebug) $values[$propertyID] = 'No CREATE permissions for this property';
      }
    }

    $newID = 0;
    //verify that there are properties to create and also that all of them have permissions
    if ((count($correctProperties) != 0) && ($propertiesCount == count($correctProperties))) {
      $newID = createItem($clientID,  $correctProperties);
      if ($newID != 0)  $values['ID'] = $newID;
    } else {
      if ($RSallowDebug) $values['error'] = 'Not created (At least 1 property has no WRITE permissions or its not visible)';
      else $values['ID'] = "NOK";
    }
    if ($RSallowDebug || array_key_exists('ID', $values)) array_push($responseArray, $values);
  }
  $response = json_encode($responseArray);

  if ($response != "[]") {
    returnJsonResponse($response);
  }
}

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkBodyIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObjectInArray($item);
  }
}
