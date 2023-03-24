<?php
//***************************************************************************************
// Description:
//    Delete one or multiple item/s of the specified itemType with the associated values
//
//  REQUEST BODY (JSON):
//  Array with object/s inside, each object must contain:
//          typeId: ID of the itemType to delete
//          itemIds: Array with the ID/IDs of the item/s to delete     
//  EXAMPLE: 
//   [{
//			"typeId": 98,
//			"itemIds": [12, 55]
//		},{
//			"typeId": 102,
//			"itemIds": [10]
//		}]	
//***************************************************************************************

deleteGivenItems();
function deleteGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();

  // definitions
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

  $response="";
  foreach ($requestBody as $itemType) {
    $itemTypeID = ParseITID($itemType->typeId, $clientID);
    $itemIDs = implode(',', $itemType->itemIds);

    // To delete an item, first we have to check that is has delete permissions for each of its properties  
    $propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

    if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
      if ($itemIDs != '') {
        deleteItems($itemTypeID, $clientID, $itemIDs);
        $response .= "[itemTypeID: ".$itemTypeID.", itemIDs: ".$itemIDs."],";
      }
    } else {
      if ($RSallowDebug) returnJsonMessage(401, "Token: ".$RStoken." does not have permissions or properties: ".implode(",".$propertiesList)." are not visible");
      else returnJsonMessage(400, "");
    }
  }
  if ($RSallowDebug) returnJsonMessage(200, "Items deleted successfully: ".rtrim($response,","));
  else returnJsonMessage(200, "");
}

// Verify if body contents are the ones expected 
function verifyBodyContent()
{
  global $RSallowDebug;

  $body = getRequestBody();
  if (!is_array($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be an array");
    else returnJsonMessage(400, "");
  }
  foreach ($body as $item) {
    if (!isset($item->typeID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'typeID'");
      else returnJsonMessage(400, "");
    }
    if (!isset($item->itemIDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'itemIDs'");
      else returnJsonMessage(400, "");
    }
    if (!is_array($item->itemIDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'itemIDs' field must be an array");
      else returnJsonMessage(400, "");
    }
  }
}
