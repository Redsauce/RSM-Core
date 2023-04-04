<?php
//***************************************************************************************
// Description:
//    Delete one or multiple item/s of the specified typeID with the associated values
//
//  REQUEST BODY (JSON):
//  Array with object/s inside, each object must contain:
//          typeID: ID of the itemType to delete
//          IDs: Array with the ID/IDs of the item/s to delete     
//  EXAMPLE: 
//   [{
//			"typeID": 98,
//			"IDs": [12, 55]
//		},{
//			"typeID": 102,
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


  $response = "[";
  foreach ($requestBody as $itemType) {
    $typeID = ParseITID($itemType->typeID, $clientID);
    $IDs = implode(',', $itemType->IDs);

    // To delete an item, first we have to check that is has delete permissions for each of its properties  
    $propertiesList = getClientItemTypePropertiesId($typeID, $clientID);

    $response .= '{"typeID": ' . $typeID . ',';
    if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
      if ($IDs != '') {
        deleteItems($typeID, $clientID, $IDs);
        //TODO - RETURN 'DELETED' OR 'NOT DELETED' DEPENDING IF ITEM EXISTS OR NOT
        foreach ($itemType->IDs as $ID) $response .= '"' . $ID . '": "Deleted",';
      }
    } else {
      foreach ($itemType->IDs as $ID) $response .= '"' . $ID . '": "Not Deleted (No DELETE permissions or properties not visible)",';
    }
    $response = rtrim($response, ",") . '},';
  }
  $response = rtrim($response, ",") . ']';

  if ($RSallowDebug and $response != "[]") {
    header('Content-Type: application/json', true, 200);
    Header("Content-Length: " . strlen($response));
    echo $response;
    die();
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
    if (!isset($item->typeID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'typeID'");
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
