<?php

getGivenItemTypes();

function getGivenItemTypes()
{
  global $RSallowDebug;

  $clientID = getClientID();
  $RStoken = getRStoken();
  $RSuserID = getRSuserID();

  if (!isset($_GET['itemTypeID']) && empty($_GET['itemTypeID'])) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), "ID");
  } else {
    $itemTypeIDs = explode(',', $_GET['itemTypeID']);
  }
  $responseArray = array();

  foreach ($itemTypeIDs as $itemTypeID) {
    $combinedArray = array();
    $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);
    if ($itemTypeIDName != "") {
      $combinedArray['itemTypeID'] = $itemTypeID;
      $combinedArray['itemID'] = $itemTypeIDName;
      $properties = getClientItemTypeProperties($itemTypeID, $clientID);
      $propertiesArray = array();
      foreach ($properties as $property) {
        // Check if user has read permission of the property
        if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
          $propertiesArray[$property['id']] = $property['name'];
        }
      }
      if (!empty($propertiesArray)) $combinedArray['properties'] = $propertiesArray;
    }
    array_push($responseArray, $combinedArray);
  }
  $response = json_encode($responseArray);
  if ($RSallowDebug and $response != "[]") {
    returnJsonResponse($response);
  } else returnJsonMessage(200, "");
}
