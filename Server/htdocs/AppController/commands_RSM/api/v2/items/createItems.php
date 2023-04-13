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

  verifyBodyContent();

  // Definitions
  $requestBody = getRequestBody();
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
        $values[$propertyID] = 'Permissions OK';
      } else {
        $values[$propertyID] = 'No CREATE permissions for this property';
      }
    }

    $newID = 0;
    //verify that there are properties to create and also that all of them have permissions
    if ((count($correctProperties) != 0) && ($propertiesCount == count($correctProperties))) {
      $newID = createItem($clientID,  $correctProperties);
      if ($newID != 0)  $values['ID'] = $newID;
    } else {
      $values['error'] = 'Not created (At least 1 property has no WRITE permissions or its not visible)';
    }
    array_push($responseArray, $values);
  }
  $response = json_encode($responseArray);

  if ($RSallowDebug and $response != "[]") {
    returnJsonResponse($response);
  } else returnJsonMessage(200, "");
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  $body = getRequestBody();
  list($code, $message) = verifyBodyContent2($body);
  if ($code == 400) returnJsonMessage($code, $message);
}

function verifyBodyContent2($body)
{
  global $RSallowDebug;
  if (!is_array($body)) {
    if ($RSallowDebug) return array(400, "Request body must be an array '[]'");
    else return array(400, "");
  }
  foreach ($body as $item) {
    if (!is_object($item)) {
      if ($RSallowDebug) return array(400, "Request body array elements must be JSON objects '{}'");
      else return array(400, "");
    }
  }
}
