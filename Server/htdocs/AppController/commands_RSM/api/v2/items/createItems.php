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

  $response = "[";
  foreach ($requestBody as $item) {
    $values = array();
    $response .= '{';
    foreach ($item as $propertyID => $propertyValue) {
      // Only prepare properties where user has CREATE permission
      if ((RShasTokenPermission($RStoken, $propertyID, "CREATE")) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
        $values[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
        $response .= '"' . $propertyID . '": "Permissions OK", ';
      } else $response .= '"' . $propertyID . '": "No CREATE permissions for this property", ';
    }

    // Create item and verify the result creation
    $newItemID = 0;
    if (count($values) != 0) $newItemID = createItem($clientID, $values);
    if ($newItemID != 0) {
      $response .= '"itemID": ' . $newItemID;
    } else {
      $response .= '"itemID": "Not Created"';
    }
    $response .= '},';
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
    if (!is_object($item)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body array elements must be JSON objects '{}'");
      else returnJsonMessage(400, "");
    }
  }
}
