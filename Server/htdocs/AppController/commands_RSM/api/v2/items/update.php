<?php
// ****************************************************************************************
//Description:
//  Edits one or more items of the specified itemTypeID with the associated values
//
//  REQUEST BODY (JSON)
// Array with object/s inside, each object must contain
//          - one propertyId and its value (one or more)
//          - id: id of of the item being updated
//  EXAMPLE:
//      [{
//          '109': 'Roja'
//          'ID': '0008'
//        },{
//          'ID':  '4001'
//          '319': "Peter
//          '320': 'Parker'
//        }]
// ****************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('PATCH');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

// Definitions
$clientID = getClientID();
$RStoken = getRStoken();
$RSuserID = getRSuserID();

foreach ($requestBody as $item) {
  $propertiesID = array();
  //Iterate through every propertyID of the items to check if they pertain to the same item type
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != 'ID') {
      $propertiesID[] = ParsePID($propertyID, $clientID);
    }
  }
  $itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);
  foreach ($propertiesID as $propertyID) {
    if (!(RShasTokenPermission($RStoken, $propertyID, 'WRITE') || isPropertyVisible($RSuserID, $propertyID, $clientID))) {
      $RSallowDebug ? returnJsonMessage(400, 'Not Updated (At least 1 property has no WRITE permissions or is not visible)')  : returnJsonMessage(400, '');
    }
  }
  $itemID = $item->ID;
  if ($itemTypeID == 0) {
    $RSallowDebug ? returnJsonMessage(400, 'Not Updated (Properties must pertain to the same item type)') : returnJsonMessage(400, '');
  } elseif (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
    $RSallowDebug ? returnJsonMessage(400, 'Item doesn\'t exist') : returnJsonMessage(400, '');
  }
}

foreach ($requestBody as $item) {
  $itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);
  $itemID = $item->ID;
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != 'ID') {
      $id = ParsePID($propertyID, $clientID);
      $propertyType = getPropertyType($id, $clientID);
      if (($propertyType == 'file') || ($propertyType == 'image')) {
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
        if (!mb_check_encoding($propertyValue, 'UTF-8')) {
          $RSallowDebug ? returnJsonMessage(400, 'Decoded parameter:' . $propertyValue . ' is not encoded in UTF-8') : returnJsonMessage(400, '');
        }
        $result = setPropertyValueByID($id, $itemTypeID, $itemID, $clientID, replaceUtf8Characters($propertyValue), $propertyType);
      }
      // Result = 0 is a successful response
      if ($result != 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Not updated') : returnJsonMessage(400, '');
      }
    }
  }
}
$RSallowDebug ? returnJsonMessage(200, 'Updated') : returnJsonMessage(200, '');

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObject($item);
    checkBodyContains($item, 'ID');
    checkIsInteger($item->ID);
  }
}
