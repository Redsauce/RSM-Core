<?php
// ****************************************************************************************
//Description:
//    Returns the number of items from the specified itemType with filter conditions
//
//  --- PARAMETERS -- All the IDs can be called as systemNames --
//   RStoken      : A token can replace the clientID
//   clientID     : ID of the client, is not necessary if the token is passed
//   propertyIDs  : string with the IDs of the properties to retrieve: ID1,ID2, ... ,IDN
//   filterRules  : string with filter conditions: ID1;base64(value1);condition1,ID2;base64(value2);condition2 ... ,IDN;base64(valueN);conditionN
//   extFilterRules: string with the ID of the external property, the value in base64, and the condition: ID;base64(value);condition
// ****************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();
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
    returnJsonMessage(400, "Invalid itemTypeID: " . $itemTypeID);
  } else {
    returnJsonMessage(400, "");
  }
}

//propertyIDs
if ($propertyIDs == '') {
  $propertyIDs = getClientItemTypePropertiesId($itemTypeID, $clientID);
}

//IDs
if ($IDs != '') {
  $IDs = implode(",", $IDs);
}

// Build an array with the filterRules
$filterProperties  = array();
if ($filterRules != '') {
  foreach ($filterRules as $rule) {
    $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => replaceUtf8Characters($rule->value), 'mode' => $rule->operation);
  }
}

// Build array with the visible propertyIds (the ones we have permissions)
$visiblePropertyIDs = array();
foreach ($propertyIDs as $singlePropertyID) {
  if (RShasTokenPermission($RStoken, $singlePropertyID, "READ") || (isPropertyVisible($RSuserID, $singlePropertyID, $clientID))) {
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
  $formattedExtFilterRules = substr_replace($formattedExtFilterRules, "", -1);
}

//GET THE ITEMS
$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, "", false, $limit = '', $IDs, "AND", 0, true, $formattedExtFilterRules, true);

//  Parse itemsArray into a JSON.
$response = array(
  "totalItems" => count($itemsArray),
);
$response = json_encode($response);

if ($response != "[]") {
  returnJsonResponse($response);
} else {
  returnJsonMessage(404, "No items were found");
}
function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContainsAtLeastOne($body, "itemTypeID", "propertyIDs");
  checkIsInteger($body->itemTypeID);
  checkIsArray($body->propertyIDs);
  checkIsArray($body->IDs);
}
