<?php
// ****************************************************************************************
//Description:
//  Creates one or more items (even from different types)
//
//  REQUEST BODY (JSON)
//  Array with object/s inside, each object must contain at least
//          - one propertyId and its value
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
  verifyBodyContent();

  // definitions
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);


  foreach ($requestBody as $item) {
    $values = array();
    foreach ($item as $propertyID => $propertyValue) {
      // Only prepare properties where user has CREATE permission
      if ((RShasTokenPermission($RStoken, $propertyID, "CREATE")) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
        $values[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
      }
    }

    // Create item and verify the result creation
    if (count($values) != 0) $newItemID = createItem($clientID, $values);
    if ($newItemID != 0) {
      $newPropertiesID[] = $newItemID;
    } else {
      $results['result'] = 'NOK';
      $results['description'] = 'CREATE FUNCTION RETURNED AN ITEMID 0';
      error_log('CREATE FUNCTION RETURNED AN ITEMID 0');
    }
  }
  // TODO 
  $results['result'] = 'OK';
  $results['itemID'] = implode(',', $newPropertiesID);
  print_r($results);
}

// Verify if body contents are the ones expected
function verifyBodyContent()
{
  $body = getRequestBody();
  if (!is_array($body)) dieWithError(400);
  foreach ($body as $item) {
    if (!is_object($item)) dieWithError(400);
  }
}
