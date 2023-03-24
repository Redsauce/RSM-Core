<?php



createGivenItems();

function createGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

  foreach ($requestBody as $item) {
    $values = array();
    foreach ($item as $propertyID => $propertyValue) {
      // Only prepare properties where user has CREATE permission
      if ((RShasTokenPermission($RStoken, $propertyID, "CREATE")) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
        $values[] = array('ID' => $propertyID, 'value' => prepareValues($propertyValue));
      }
    }

    if (count($values) != 0) $newItemID = createItem($clientID, $values);
    if ($newItemID != 0) {
      $newPropertiesID[] = $newItemID;
    } else {
      if ($RSallowDebug) returnJsonMessage(400, "CREATE FUNCTION RETURNED AN ITEMID 0");
      else returnJsonMessage(400, "");
    }
  }
  returnJsonMessage(200, "Items created successfully: ".implode(",",$newPropertiesID));
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  global $RSallowDebug;

  $body = getRequestBody();
  if (!is_array($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be an array '[]' of JSON objects '{}'");
    else returnJsonMessage(400, "");
  }
  foreach ($body as $item) {
    if (!is_object($item)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body array elements must be JSON objects '{}'");
      else returnJsonMessage(400, "");
    }
  }
}
