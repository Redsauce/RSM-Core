<?php
//***************************************************************************************
// Description:
//    Get one or multiple item/s of the specified itemType with the associated values
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
// {
//     "IDs": ["571"],
//     "itemTypeID": "8"
// }
//  EXAMPLE 2:
// {
//     "IDs": ["571", "569"],
//     "propertyIDs": ["58","59"]
// }
//  EXAMPLE 3:
// {
//     "propertyIDs": ["59"],
//     "filterRules":
//      [
//          {
//              "propertyID': "58",
//              "value": "John",
//              "operation": "="
//          }.
//          {
//              "propertyID": "59",
//              "value": "Doe",
//              "operation": "<>"
//          }
//      ]
// }
//***************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';

// Definitions
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

// Params
$propertyIDs = $requestBody->propertyIDs;
$filterRules = $requestBody->filterRules;
$extFilterRules = $requestBody->extFilterRules;
$originalIDs = $requestBody->IDs;
$itemTypeID = $requestBody->itemTypeID;

// includeCategories filter
$includeCategories = false;
if (isset($requestBody->includeCategories) && $requestBody->includeCategories) {
  $includeCategories = true;
}

// translateIDs
$translateIDs = false;
if (isset($requestBody->translateIDs) && $requestBody->translateIDs) {
  $translateIDs = true;
}

// itemTypeID
if ($itemTypeID == '') {
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
}

if ($itemTypeID <= 0) {
  $RSallowDebug ? returnJsonMessage(400, 'Invalid itemTypeID: ' . $itemTypeID) : returnJsonMessage(400, '');
}

//propertyIDs
if ($propertyIDs == '') {
  $propertyIDs = getClientItemTypePropertiesId($itemTypeID, $clientID);
}
// IDs
if (is_array($originalIDs)) {
  $IDs = implode(',', $originalIDs);
} else {
  $IDs = $originalIDs;
}

// Build an array with the filterRules
$filterProperties = array();
if (is_array($filterRules) && !empty($filterRules)) {
  foreach ($filterRules as $rule) {
    $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
  }
}

// Build array with the visible propertyIds (if they is visible for us, then we have permissions)
$visiblePropertyIDs = array();

if (is_array($propertyIDs)) {
  foreach ($propertyIDs as $singlePropertyID) {
    if (RShasTokenPermission($RStoken, $singlePropertyID, 'READ') || (isPropertyVisible($RSuserID, $singlePropertyID, $clientID))) {
      $visiblePropertyIDs[] = array('ID' => ParsePID($singlePropertyID, $clientID), 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
    }
  }
}


// Build a string with the extFilterRules
$formattedExtFilterRules = '';
if (is_array($extFilterRules) && !empty(($extFilterRules))) {
  foreach ($extFilterRules as $singleRule) {
    // To use getFilteredItemsIDs function without changing the original php's, we need to transform the following data into an specific format (base64)
    $formattedExtFilterRules .=  $singleRule->propertyID . ';' . ($singleRule->value) . ';' . $singleRule->operation . ',';
  }
  $formattedExtFilterRules = trim($formattedExtFilterRules, ',');
}

// GET THE ITEMS
$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, '', $translateIDs, $limit = '', $IDs, 'AND', 0, true, $formattedExtFilterRules, true);
$responseArray = array();

// To construct the response, we have to verify if the includecategories filter is true
if ($includeCategories) {
  // obtain all the corresponding properties and its categories
  $categorizedProperties = getPropertiesExtendedForToken($itemTypeID, $RStoken, $visiblePropertyIDs);
  // parse all the different items of the original response
  foreach ($itemsArray as $item) {
    $combinedArray = array();
    $combinedArray['ID'] = $item['ID'];
    // loop through the categories and save its values
    foreach ($categorizedProperties as $property) {
      $category = $property['Category'];
      $propertyID = $property['propertyID'];
      // save the values in the new array, with its corresponding categories
      if (isset($item[$propertyID])) {
        $combinedArray[$category][$propertyID] = html_entity_decode($item[$propertyID]);
      } else {
        $combinedArray[$category][$propertyID] = '';
      }
    }
    // construct the response array by pushing each one of the items
    array_push($responseArray, $combinedArray);
  }
} else {
  // Parse itemsArray into a JSON.
  foreach ($itemsArray as $item) {
    $combinedArray = array();
    foreach ($item as $propertyKey => $propertyValue) {
      $combinedArray[$propertyKey] = html_entity_decode($propertyValue);
    }
    array_push($responseArray, $combinedArray);
  }
}

if (!empty($responseArray)) {
  returnJsonResponse(json_encode($responseArray));
} else {
  returnJsonMessage(200, '{}');
}


// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContainsAtLeastOne($body, 'itemTypeID', 'propertyIDs');
  checkIsArray($body->propertyIDs);
  checkIsArray($body->IDs);
  if (isset($body->filterRules)) {
    checkIsArray($body->filterRules);
  }
  if (isset($body->extFilterRules)) {
    checkIsArray($body->extFilterRules);
  }
}
