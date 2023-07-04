<?php
//***************************************************************************************
// Description:
//    Delete one or multiple item/s of the specified itemTypeID with the associated values
//
//  REQUEST BODY (JSON):
//  Array with object/s inside, each object must contain:
//          itemTypeID: ID of the itemType to delete
//          IDs: Array with the ID/IDs of the item/s to delete
//  EXAMPLE:
//   [{
//      "itemTypeID": 98,
//      "IDs": [12, 55]
//      },{
//      "itemTypeID": 102,
//      "IDs": [10]
//    }]
//***************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('DELETE');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

// Definitions
$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

$responseArray = array();

foreach ($requestBody as $itemType) {
  $combinedArray = array();
  $itemTypeID = ParseITID($itemType->itemTypeID, $clientID);

  // To delete an item, first we have to check that is has delete permissions for each of its properties
  $propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

  if (!((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID)))) {
    $RSallowDebug ? returnJsonMessage(400, "Not Deleted (At least 1 property has no DELETE permissions or its not visible)") : returnJsonMessage(400, "");
  }
  if (count($itemType->IDs) != 0) {
    foreach ($itemType->IDs as $ID) {
      if (!verifyItemExists($ID, $itemTypeID, $clientID)) {
        returnJsonMessage(400, "Item doesn't exist");
      }
    }
  }
}
foreach ($requestBody as $itemType) {
  deleteItems($itemType->itemTypeID, $clientID, implode(',', $itemType->IDs));
}

$RSallowDebug ? returnJsonMessage(200, "Deleted") : returnJsonMessage(200, "");

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObject($item);
    checkBodyContains($item, "itemTypeID");
    checkBodyContains($item, "IDs");
    checkIsArray($item->IDs);
  }
}
