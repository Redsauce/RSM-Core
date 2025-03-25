<?php
// ****************************************************************************************
//Description:
//  Edits one or more items of the specified itemTypeID with the associated values
//
//  REQUEST BODY (JSON)
// Array with object/s inside, each object must contain
//          - one propertyId and its value (one or more)
//          - id: id of the item being updated
//  EXAMPLE:
//      [{
//          "109": "Roja",
//          "ID": "8"
//        },{
//          "ID":  "4001",
//          "319": "Peter",
//          "320": "Parker"
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
$RStoken = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

foreach ($requestBody as $item) {
  $propertyIDs = array();
  //Iterate through every propertyID of the items to check if they pertain to the same item type
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != 'ID') {
      $propertyIDs[] = ParsePID($propertyID, $clientID);
    }
  }
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
  foreach ($propertyIDs as $propertyID) {
    if (!(RShasTokenPermission($RStoken, $propertyID, 'WRITE') || isPropertyVisible($RSuserID, $propertyID, $clientID))) {
      $RSallowDebug ? returnJsonMessage(400, 'Not Updated (Property ' . $propertyID . ' has no WRITE permissions or is not visible)')  : returnJsonMessage(400, '');
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
  $propertyIDs = array();
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != 'ID') {
      $propertyIDs[] = ParsePID($propertyID, $clientID);
    }
  }
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
  $itemID = $item->ID;
  foreach ($item as $unparsedPropertyID => $propertyValue) {
    if ($unparsedPropertyID != 'ID') {
      $propertyID = ParsePID($unparsedPropertyID, $clientID);
      $propertyType = getPropertyType($propertyID, $clientID);
      if (($propertyType == 'file') || ($propertyType == 'image')) {
        $pieces = explode(':', $propertyValue);
        if (count($pieces) == 1) {
          $name = '';
          $value = $pieces[0];
        } elseif (count($pieces) == 2) {
          $name = $pieces[0];
          $value = $pieces[1];
        } else {
          $RSallowDebug ? returnJsonMessage(400, 'Error processing file/image') : returnJsonMessage(400, '');
        }

        if ($value == '') {
          deleteItemPropertyValue($itemTypeID, $itemID, $propertyID, $clientID, $propertyType);
        } else {
          $result = setDataPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $name, $value, $propertyType, $RSuserID);
        }
      } else {
        if (!mb_check_encoding($propertyValue, 'UTF-8')) {
          $RSallowDebug ? returnJsonMessage(400, 'Decoded parameter:' . $propertyValue . ' is not encoded in UTF-8') : returnJsonMessage(400, '');
        }
        $result = setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, replaceUtf8Characters($propertyValue), $propertyType);
      }
      // Result = 0 is a successful response
      if ($result != 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Item ' . $itemID . ' not updated') : returnJsonMessage(400, '');
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
    checkStringIsInteger($item->ID);
  }
}
