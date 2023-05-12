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
//			"itemTypeID": 98,
//			"IDs": [12, 55]
//		},{
//			"itemTypeID": 102,
//			"IDs": [10]
//		}]	
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
  $IDs = implode(',', $itemType->IDs);

  // To delete an item, first we have to check that is has delete permissions for each of its properties  
  $propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

  if ($RSallowDebug) $combinedArray["itemTypeID"] = $itemTypeID;
  if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
    if ($IDs != '') {
      // Check and separate ID'S that exist from the ones that doesn't. Only delete the ones that exist
      $existingItemIDs = array();
      $notExistingItemIDs =  array();

      foreach ($itemType->IDs as $ID) {
        if (verifyItemExists($ID, $itemTypeID, $clientID)) {
          $existingItemIDs[] = $ID;
        } else {
          $notExistingItemIDs[] = $ID;
        }
      }
      // only call delete function, when there are items to delete.
      if ((implode(',', $existingItemIDs)) != '') deleteItems($itemTypeID, $clientID, implode(',', $existingItemIDs));

      foreach ($existingItemIDs as $ID) $RSallowDebug ? $combinedArray[$ID] = "Deleted" : $combinedArray[$ID] = "OK";
      foreach ($notExistingItemIDs as $ID) $RSallowDebug ? $combinedArray[$ID] = "Item doesn't exist" : $combinedArray[$ID] = "NOK";
    }
  } else {
    foreach ($itemType->IDs as $ID) {
      $RSallowDebug ? $combinedArray[$ID] = "Not deleted" : $combinedArray[$ID] = "NOK";
    }
  }
  array_push($responseArray, $combinedArray);
}
$response = json_encode($responseArray);

if ($response != "[]") {
  returnJsonResponse($response);
}


// Verify if body contents are the ones expected 
function verifyBodyContent($body)
{
  checkBodyIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObjectInArray($item);
    checkBodyContainsItemTypeID($item);
    checkBodyContainsIDs($item);
    checkIDsIsAnArray($item);
  }
}
