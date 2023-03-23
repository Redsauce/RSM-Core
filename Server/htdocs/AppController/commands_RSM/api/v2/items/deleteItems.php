<?php

deleteGivenItems();

function deleteGivenItems()
{
  verifyBodyContent();
  $requestBody = getRequestBody();
  isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
  isset($GLOBALS['RS_POST']['RStoken']) ? $RStoken = $GLOBALS['RS_POST']['RStoken'] : dieWithError(400);
  isset($GLOBALS['RSuserID']) ? $RSuserID = $GLOBALS['RSuserID'] : dieWithError(400);

  foreach ($requestBody as $itemType) {
    $itemTypeID = ParseITID($itemType->typeId, $clientID);
    $itemIDs = implode(',', $itemType->itemIds);
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
