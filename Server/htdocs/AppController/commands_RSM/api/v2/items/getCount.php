<?php
// ****************************************************************************************
//Description:
//    Returns the number of items from the specified itemType with filter conditions
//
//  REQUEST BODY (JSON):
//  IDs: Array with the specific item IDs to retrieve.
//  itemTypeID: Item type from which the IDs should be retrieved.
//  propertiesIDs: ID of the properties to be retrieved from the item.
//  filtersRules: Array with the different filters to be applied. Each filter consists of:
//  propertyID: ID of the property to filter by.
//  value: Value used as the filter.
//  operation: Criteria used to determine if the value equals, is greater, is less...
//  extFilterRules: Same as filterRules, but using a propertyID from another itemType.

// ****************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken =  getRStoken();
$clientID = getClientID();

//Params
$propertyIDs = $requestBody->propertyIDs;
$filterRules = $requestBody->filterRules;
$extFilterRules = $requestBody->extFilterRules;
$IDs = $requestBody->IDs;
$itemTypeID = $requestBody->itemTypeID;

//itemTypeID
if ($itemTypeID == '') {
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
}
if ($itemTypeID <= 0) {
  if ($RSallowDebug) {
    returnJsonMessage(400, 'Invalid itemTypeID: ' . $itemTypeID);
  } else {
    returnJsonMessage(400, '');
  }
}

//propertyIDs
if ($propertyIDs == '') {
  $propertyIDs = getClientItemTypePropertiesId($itemTypeID, $clientID);
}

// Build array with the visible propertyIds (the ones we have permissions)
$visiblePropertyIDs = array();
foreach ($propertyIDs as $singlePropertyID) {
  if (RShasTokenPermission($RStoken, $singlePropertyID, 'READ')) {
    $visiblePropertyIDs[] = array('ID' => ParsePID($singlePropertyID, $clientID), 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
    if (empty($visiblePropertyIDs)) {
      returnJsonMessage(200, '0');
    }
  }
}

//IDs
if ($IDs != '') {
  $implodedIDs = implode(',', $IDs);
}

// Build an array with the filterRules
$filterProperties  = array();
if ($filterRules != '') {
  foreach ($filterRules as $rule) {
    $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
  }
}

// Build a string with the extFilterRules
$formattedExtFilterRules = '';
if ($extFilterRules != '') {
  foreach ($extFilterRules as $singleRule) {
    // To use getFilteredItemsIDs function without changing the original php's, we need to transform the following data into an specific format (base64)
    $formattedExtFilterRules  .=  $singleRule->propertyID . ';' . base64_encode($singleRule->value) . ';' . $singleRule->operation . ',';
  }
  $formattedExtFilterRules = substr_replace($formattedExtFilterRules, '', -1);
}

//GET THE ITEMS
$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, '', false, $limit = '', $implodedIDs, 'AND', 0, true, $formattedExtFilterRules, true);

//  Parse itemsArray into a JSON.
$response = array(
  'count' => count($itemsArray),
);

if (count($itemsArray) != 0) {
  $response = json_encode($response);
  returnJsonResponse($response);
} else {
  if ($RSallowDebug) {
    returnJsonMessage(200, 'No items were found');
  } else {
    returnJsonMessage(200, '');
  }
}
function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContainsAtLeastOne($body, 'itemTypeID', 'propertyIDs');
  checkIsArray($body->propertyIDs);
  checkIsArray($body->IDs);
}
