<?php
// ****************************************************************************************
//Description:
//    Get the number of items from the specified itemType with filter conditions
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
// {
//     'IDs': [571],
//     'itemTypeID': 8
// }
//  EXAMPLE 2:
// {
//     'IDs': [571, 569],
//     'propertyIDs': [58,59]
// }
//  EXAMPLE 3:
// {
//     'propertyIDs': [59],
//     'filtersRules':
//      [
//          {
//              'propertyID': 58,
//              'value': 'Sergio',
//              'operation': '='
//          }.
//          {
//              'propertyID': 59,
//              'value': 'Santamaria',
//              'operation': '<>'
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
$IDs = $requestBody->IDs;
$itemTypeID = $requestBody->itemTypeID;

// itemTypeID
if ($itemTypeID == '') {
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
}

if ($itemTypeID <= 0) {
    $RSallowDebug ? returnJsonMessage(400, 'Invalid itemTypeID: ' . $itemTypeID) : returnJsonMessage(400, '');
}

// propertyIDs
if ($propertyIDs == '') {
  $propertyIDs = getClientItemTypePropertiesId($itemTypeID, $clientID);
}

// IDs
if ($IDs != '') {
  $IDs = implode(",", $IDs);
}
// Build an array with the filterRules
$filterProperties = array();
if ($filterRules != '') {
  foreach ($filterRules as $rule) {
    $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
  }
}

// Build array with the visible propertyIds (if they is visible for us, then we have permissions)
$visiblePropertyIDs = array();
foreach ($propertyIDs as $singlePropertyID) {
  if (RShasTokenPermission($RStoken, $singlePropertyID, 'READ') || (isPropertyVisible($RSuserID, $singlePropertyID, $clientID))) {
    $visiblePropertyIDs[] = array('ID' => ParsePID($singlePropertyID, $clientID), 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
  }
}
// Build a string with the extFilterRules
$formattedExtFilterRules = "";
if ($extFilterRules != '') {
  foreach ($extFilterRules as $singleRule) {
    // To use getFilteredItemsIDs function without changing the original php's, we need to transform the following data into an specific format (base64)
    $formattedExtFilterRules  .=  $singleRule->propertyID . ";" . base64_encode($singleRule->value) . ";" . $singleRule->operation . ',';
  }
  $formattedExtFilterRules = trim($formattedExtFilterRules, ',');
}

// GET THE ITEMS
$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, '', false, $limit = '', $IDs, "AND", 0, true, $formattedExtFilterRules, true);

if (!empty($itemsArray)) {
    returnJsonResponse(json_encode(['totalItems' => count($itemsArray)]));
} else {
    $RSallowDebug ? returnJsonMessage(404, 'No items were found') : returnJsonMessage(404, '');
}

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContainsAtLeastOne($body, 'itemTypeID', 'propertyIDs');
  checkIsInteger($body->itemTypeID);
  checkIsArray($body->propertyIDs);
  checkIsArray($body->IDs);
  if (isset($body->filterRules)) {
    checkIsArray($body->filterRules);
  }
  if (isset($body->extFilterRules)) {
    checkIsArray($body->extFilterRules);
  }
}
