<?php
//***************************************************************************************
// Description:
//    Get one, multiple or all item types and its associated propertyIDS + NAME 
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1: 
//    -Use the endpoint without any param or body to obtain all of them
//
//  EXAMPLE 2
//    - Add a query param with the specific itemtypeIDs
//            ?ID=6,7,8
//***************************************************************************************

getGivenItemTypes();

function getGivenItemTypes()
{
  global $RSallowDebug;

  $clientID = getClientID();
  $RStoken = getRStoken();
  $RSuserID = getRSuserID();

  if (!isset($_GET['ID']) && empty($_GET['ID'])) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), "ID");
  } else {
    $itemTypeIDs = explode(',', $_GET['ID']);
  }
  $responseArray = array();

  foreach ($itemTypeIDs as $itemTypeID) {
    $combinedArray = array();
    $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);
    if ($itemTypeIDName != "") {
      $combinedArray['itemTypeID'] = $itemTypeID;
      $combinedArray['name'] = $itemTypeIDName;
      $properties = getClientItemTypeProperties($itemTypeID, $clientID);
      $propertiesArray = array();
      foreach ($properties as $property) {
        // Check if user has read permission of the property
        if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
          $propertiesArray[$property['id']] = $property['name'];
        }
      }
      if (!empty($propertiesArray)) $combinedArray['properties'] = $propertiesArray;
      array_push($responseArray, $combinedArray);
    }
  }
  $response = json_encode($responseArray);
  if ($RSallowDebug and $response != "[]") {
    returnJsonResponse($response);
  } else if ($RSallowDebug and $response == "[]") {
    returnJsonMessage(404, "No ItemTypeIDs were found.");
  } else returnJsonMessage(200, "");
}
