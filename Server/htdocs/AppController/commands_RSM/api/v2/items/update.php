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

$responseArray = array();
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
    if ($RSallowDebug) {
      $responseArray['error'] = "Not Updated (Incongruent properties)";
    } else {
      $responseArray['error'] = "NOK";
    }
    break;
  } elseif (!$hasAllPermissions) {
    if ($RSallowDebug) {
      $responseArray['error'] = "Not Updated (At least 1 property has no WRITE permissions or its not visible)";
    } else {
      $responseArray['error'] = "NOK";
    }
    break;
  } elseif (!verifyItemExists($itemID, $itemTypeIDID, $clientID)) {
    if ($RSallowDebug) {
      $responseArray['error'] = "Item doesn't exist";
    } else {
      $responseArray['error'] = "NOK";
    }
    break;
  }
}

if (!isset($responseArray['error'])) {
  foreach ($requestBody as $item) {
    $combinedArray = array();
    if ($RSallowDebug) {
      $itemTypeIDID = getItemTypeIDFromProperties($propertiesID, $clientID);
      $combinedArray['itemTypeID'] = intval($itemTypeIDID);
    }
    $itemID = $item->ID;
    $combinedArray['ID'] = $itemID;
    foreach ($item as $propertyID => $propertyValue) {
      if ($propertyID != "ID") {
        $id = ParsePID($propertyID, $clientID);
        $propertyType = getPropertyType($id, $clientID);
        if (($propertyType == 'file') || ($propertyType == 'image')) {
          //TODO - ASK ON HOW UPDATE FILE/IMAGE SHOULD WORK AND WHY ":" IS NEEDED
        } else {
          if (!mb_check_encoding($propertyValue, "UTF-8")) {
            if ($RSallowDebug) {
              returnJsonMessage(400, "Decoded parameter:" . $propertyValue . " is not UTF-8 valid");
            } else {
              returnJsonMessage(400, "");
            }
          }
          $parsedValue = replaceUtf8Characters($propertyValue);
          $result = setPropertyValueByID($id, $itemTypeIDID, $itemID, $clientID, $parsedValue, $propertyType);
        }
        // Result = 0 is a successful response
        if ($result != 0) {
          $RSallowDebug ? $combinedArray[$propertyID] = 'Not Updated (' . $result . ')' : $combinedArray[$propertyID] = 'NOK';
        } else {
          $RSallowDebug ? $combinedArray[$propertyID] = 'Updated' : $combinedArray[$propertyID] = 'OK';
        }
      }
    }
    array_push($responseArray, $combinedArray);
  }
}

$response = json_encode($responseArray);

if ($response != "[]") {
  returnJsonResponse($response);
}

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
