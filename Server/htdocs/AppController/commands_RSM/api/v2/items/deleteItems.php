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

deleteGivenItems();
function deleteGivenItems()
{
  global $RSallowDebug;

  verifyBodyContent();

  // Definitions
  $requestBody = getRequestBody();
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

    $combinedArray["itemTypeID"] = $itemTypeID;
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

        foreach ($existingItemIDs as $ID)  $combinedArray[$ID] = "OK";
        foreach ($notExistingItemIDs as $ID)  $combinedArray[$ID] = "Item does not exist";
      }
    } else {
      foreach ($itemType->IDs as $ID) {
        $combinedArray[$ID] = "NOK";
      }
    }
    array_push($responseArray, $combinedArray);
  }
  $response = json_encode($responseArray);

  if ($RSallowDebug and $response != "[]") {
    returnJsonResponse($response);
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
    if (!isset($item->itemTypeID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'itemTypeID'");
      else returnJsonMessage(400, "");
    }
    if (!isset($item->IDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'IDs'");
      else returnJsonMessage(400, "");
    }
    if (!is_array($item->IDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'IDs' field must be an array '[]'");
      else returnJsonMessage(400, "");
    }
  }
}