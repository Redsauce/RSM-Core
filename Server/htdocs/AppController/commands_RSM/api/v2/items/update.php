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
//          "109": "Roja"
//          "ID": "0008"
//        },{
//          "ID":  "4001"
//          "319": "Peter
//          "320": "Parker"
//        }]
// ****************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('PATCH');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

// Definitions
$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

foreach ($requestBody as $item) {
  $propertiesID = array();
  //Iterate through every propertyID of the items to check if they are incongruent
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != "id" && $propertyID != "ID") {
      $propertiesID[] = ParsePID($propertyID, $clientID);
    }
  }
  $itemTypeIDID = getItemTypeIDFromProperties($propertiesID, $clientID);
  $hasAllPermissions = checkTokenHasWritePermissions($RStoken, $RSuserID, $clientID, $propertiesID);
  $itemID = $item->ID;
  if ($itemTypeIDID == 0) {
    $RSallowDebug ? returnJsonMessage(400, "Not Updated (Incongruent properties)")  : returnJsonMessage(400, "");
    break;
  } elseif (!$hasAllPermissions) {
    $RSallowDebug ? returnJsonMessage(400, "Not Updated (At least 1 property has no WRITE permissions or its not visible)")  : returnJsonMessage(400, "");
    break;
  } elseif (!verifyItemExists($itemID, $itemTypeIDID, $clientID)) {
    $RSallowDebug ? returnJsonMessage(400, "Item doesn't exist")  : returnJsonMessage(400, "");
    break;
  }
}

foreach ($requestBody as $item) {
  $itemTypeIDID = getItemTypeIDFromProperties($propertiesID, $clientID);
  $itemID = $item->ID;
  foreach ($item as $propertyID => $propertyValue) {
    if ($propertyID != "ID") {
      $id = ParsePID($propertyID, $clientID);
      $propertyType = getPropertyType($id, $clientID);
      if (($propertyType == 'file') || ($propertyType == 'image')) {
        //TODO - Pending on test server to test files and images
      } else {
        if (!mb_check_encoding($propertyValue, "UTF-8")) {
          $RSallowDebug ? returnJsonMessage(400, "Decoded parameter:" . $propertyValue . " is not UTF-8 valid") : returnJsonMessage(400, "");
        }
        $result = setPropertyValueByID($id, $itemTypeIDID, $itemID, $clientID, replaceUtf8Characters($propertyValue), $propertyType);
      }
      // Result = 0 is a successful response
      if ($result != 0) {
        $RSallowDebug ? returnJsonMessage(400, "Not updated") : returnJsonMessage(400, "");
      }
    }
  }
}
$RSallowDebug ? returnJsonMessage(200, "Updated") : returnJsonMessage(200, "");

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObject($item);
    checkBodyContains($item, "ID");
    checkIsInteger($item->ID);
  }
}

function checkTokenHasWritePermissions($rstoken, $rsuserID, $clientID, $propertiesID)
{
  foreach ($propertiesID as $propertyID) {
    if (RShasTokenPermission($rstoken, $propertyID, "WRITE") || isPropertyVisible($rsuserID, $propertyID, $clientID)) {
      continue;
    }
    return false;
  }
  return true;
}
