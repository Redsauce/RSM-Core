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
  verifyBodyContent();

  // definitions
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

  foreach ($requestBody as $itemType) {
    $itemTypeID = ParseITID($itemType->typeId, $clientID);
    $itemIDs = implode(',', $itemType->itemIds);

    // To delete an item, first we have to check that is has delete permissions for each of its properties  
    $propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

    if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
      if ($itemIDs != '') {
        deleteItems($itemTypeID, $clientID, $itemIDs);
      }
    } else {
      dieWithError(401);
    }
  }
}

// Verify if body contents are the ones expected 
function verifyBodyContent()
{
  $body = getRequestBody();
  if (!is_array($body)) dieWithError(400);
  foreach ($body as $item) {
    if (!isset($item->typeId)) dieWithError(400);
    if (!isset($item->itemIds)) dieWithError(400);
    if (!is_array($item->itemIds)) dieWithError(400);
  }
}
