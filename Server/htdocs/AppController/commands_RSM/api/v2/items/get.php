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
handleApiCorsPreflight(array('GET', 'POST'));
setAuthorizationTokenOnGlobals();

checkCorrectRequestMethod(array('GET', 'POST'));

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../../utilities/RSMlistsManagement.php';
// Definitions
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

// Params
$propertyIDs          = isset($requestBody->propertyIDs) ? $requestBody->propertyIDs : '';
$filterRules          = isset($requestBody->filterRules) ? $requestBody->filterRules : array();
$extFilterRules       = isset($requestBody->extFilterRules) ? $requestBody->extFilterRules : array();
$originalIDs          = isset($requestBody->IDs) ? $requestBody->IDs : '';
$itemTypeID           = isset($requestBody->itemTypeID) ? $requestBody->itemTypeID : '';

// includeCategories filter
$includeCategories = false;
if (isset($requestBody->includeCategories) && $requestBody->includeCategories) {
  $includeCategories = true;
}

// translateIDs
$translateIDs = false;
if (isset($requestBody->translateIDs) && $requestBody->translateIDs) {
  $translateIDs = true;
  // v1 reads translateIDs from the sanitized POST globals inside RShasTokenPermission().
  // v2 receives JSON, so mirror the flag there to reuse the same permission rule.
  $GLOBALS[$cstRS_POST]['translateIDs'] = 'true';
}

// systemNames
$systemNames = false;
if (isset($requestBody->systemNames) && $requestBody->systemNames) {
  $systemNames = true;
}

// itemTypeID. Ignore systemNames if we don't use de itemTypeID
if ($itemTypeID == '') {
  $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
  $systemNames = false;
} else {
  $itemTypeID = ParseITID($itemTypeID, $clientID);
}

if ($itemTypeID <= 0) {
  $RSallowDebug ? returnJsonMessage(400, 'Invalid itemTypeID: ' . $itemTypeID) : returnJsonMessage(400, '');
}

//propertyIDs
$allPropertiesRequested = ($propertyIDs == '');
if ($allPropertiesRequested) {
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
    $filterProperties[] = array('ID' => parsePID($rule->propertyID, $clientID), 'value' => parseProperyListValue($rule->value, $clientID), 'mode' => $rule->operation);
  }
}

// Build array with the visible propertyIds (if they is visible for us, then we have permissions)
$visiblePropertyIDs = array();

if (is_array($propertyIDs)) {
  foreach ($propertyIDs as $singlePropertyID) {
    $parsedPropertyID = ParsePID($singlePropertyID, $clientID);
    $hasTokenReadPermission = RShasTokenPermission($RStoken, $parsedPropertyID, 'READ');
    $hasVisibleProperty = isPropertyVisible($RSuserID, $parsedPropertyID, $clientID);
    if (!$hasTokenReadPermission && !$hasVisibleProperty) {
      // v1 validates parsed property IDs. Keep doing that here so system names
      // and numeric IDs follow the same translated-identifier permission path.
      if (!$allPropertiesRequested) {
        $RSallowDebug ? returnJsonMessage(403, 'No permissions to read these items') : returnJsonMessage(403, '');
      }
      continue;
    }

    $visiblePropertyIDs[] = array('ID' => $parsedPropertyID, 'name' => $singlePropertyID, 'trName' => $singlePropertyID . 'trs');
  }
}

// Build a string with the extFilterRules
$formattedExtFilterRules = '';
if (is_array($extFilterRules) && !empty(($extFilterRules))) {
  foreach ($extFilterRules as $singleRule) {
    // To use getFilteredItemsIDs function without changing the original php's, we need to transform the following data into an specific format (base64)
    $formattedExtFilterRules .=  $singleRule->propertyID . ';' . base64_encode($singleRule->value) . ';' . $singleRule->operation . ';' . base64_encode($singleRule->path) . ",";
  }
  $formattedExtFilterRules = trim($formattedExtFilterRules, ',');
}

// GET THE ITEMS
// Customer-scoped tokens are restricted by adding their customer identifier as a normal filter.
$filterProperties = RSappendTokenCustomerScopeFilter($RStoken, $clientID, $itemTypeID, $filterProperties);
if ($filterProperties === false) {
  $RSallowDebug ? returnJsonMessage(403, 'Token customer scope does not allow access to this item type') : returnJsonMessage(403, '');
}

$itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $visiblePropertyIDs, '', $translateIDs, $limit = '', $IDs, 'AND', 0, !$includeCategories, $formattedExtFilterRules, true);
$responseArray = array();

// To construct the response, we have to verify if the includecategories filter is true
if ($includeCategories) {

  // The properties in $visiblePropertyIDs have already passed the endpoint's
  // permission/visibility checks. Categories do not have their own permissions.
  $categorizedProperties = getVisiblePropertiesWithCategories($itemTypeID, $clientID, $visiblePropertyIDs);
  // parse all the different items of the original response
  foreach ($itemsArray as $item) {
    $combinedArray = array();
    $combinedArray['ID'] = $item['ID'];

    // Categories are only added when they contain at least one property visible
    // to the caller.
    foreach ($categorizedProperties as $property) {
      $category = $property['Category'];
      $propertyID = $property['propertyID'];
      $itemPropertyKey = $property['propertyKey'];
      $outputPropertyKey = $itemPropertyKey;

      if ($systemNames) {
        $systemName = getAppPropertyName_RelatedWith($propertyID, $clientID);
        if ($systemName !== '') {
          $outputPropertyKey = $systemName;
        }
      }

      // save the values in the new array, with its corresponding categories
      if (isset($item[$itemPropertyKey])) {
        $combinedArray[$category][$outputPropertyKey] = html_entity_decode($item[$itemPropertyKey]);
      } elseif (isset($item[$propertyID])) {
        $combinedArray[$category][$outputPropertyKey] = html_entity_decode($item[$propertyID]);
      } else {
        $combinedArray[$category][$outputPropertyKey] = '';
      }
    }
    // construct the response array by pushing each one of the items
    array_push($responseArray, $combinedArray);
  }
} else {

  $responseArray = [];

  // The $itemsArray response may come either as a path to a temporary file or as an array

  // CASE A: Path to an existing file
  if (is_string($itemsArray) && file_exists($itemsArray)) {   
    $xml = simplexml_load_file($itemsArray, "SimpleXMLElement", LIBXML_NOCDATA);

    if ($xml !== false) {

      foreach ($xml->rows->row as $row) {
        $combinedArray = [];
        foreach ($row->column as $column) {
          $columnName = (string) $column['name'];
          $propertyValue = (string) $column;
          $combinedArray[$columnName] = html_entity_decode($propertyValue);
        }
        // We append the processed row array to our final response.
        $responseArray[] = $combinedArray;
      }
    } else {
      error_log("Error parsing the XML file from path: " . $itemsArray);
    }
  }

  // CASE B: Array with already structured data
  else {

    // Maps the properties provided by the client to the corresponding property ID
    // We want to preserve the property names exactly as sent by the client
    $propertyIDsDictionary = array();
    if (is_array($propertyIDs)) {
      foreach ($propertyIDs as $rawPropertyID) {
        $rawPropertyID = trim($rawPropertyID);

        if ($rawPropertyID === '') {
          continue;
        }

        if (is_numeric($rawPropertyID)) {
          $propertyIDsDictionary[(string) $rawPropertyID] = (string) $rawPropertyID;
        } else {
          $tempSystemProperty = getPropertyIDs_usingSysName(array($rawPropertyID), $clientID);
          if (is_array($tempSystemProperty) && isset($tempSystemProperty[0]["ID"]) && isset($tempSystemProperty[0]["appName"])) {
            $propertyIDsDictionary[$tempSystemProperty[0]["ID"]] = $tempSystemProperty[0]["appName"];
          }
        }
      }
    }

    // We only iterate to apply html_entity_decode
    foreach ($itemsArray as $item) {
      $decodedItem = [];
      foreach ($item as $key => $value) {
        // Resolve output key using the previous mapping, if available
        $resolvedKey = isset($propertyIDsDictionary[(string)$key]) ? $propertyIDsDictionary[(string)$key] : $key;

        // If systemNames is true, get the system name directly and use it if not empty
        if ($systemNames) {

          $keyString = (string)$key;
          $keyWithoutTrs = $keyString;
          $hasTrs = false;
          
          // Check if key ends with "trs"
          if (substr($keyString, -3) === 'trs') {
            $keyWithoutTrs = substr($keyString, 0, -3);
            $hasTrs = true;
          }
          
          $systemName = getAppPropertyName_RelatedWith($keyWithoutTrs, $clientID);
          
          // If key had "trs", add it back to the system name
          if ($hasTrs && $systemName !== '') {
            $systemName = $systemName . 'trs';
          }        
          
          if ($systemName !== '') {
            $resolvedKey = $systemName;
          }

        }

        $decodedItem[$resolvedKey] = is_string($value) ? html_entity_decode($value) : $value;
      }
      // We append the decoded row array to our final response.
      $responseArray[] = $decodedItem;
    }
  }
}

if (!empty($responseArray)) {
  returnJsonResponse(json_encode($responseArray));
} else {
  returnJsonResponse('{}');
}


// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContainsAtLeastOne($body, 'itemTypeID', 'propertyIDs');
  if (isset($body->propertyIDs)) checkIsArray($body->propertyIDs);
  if (isset($body->IDs)) checkIsArray($body->IDs);
  if (isset($body->filterRules)) checkIsArray($body->filterRules);
  if (isset($body->extFilterRules)) checkIsArray($body->extFilterRules);
}

// Return category metadata for properties already authorized by this endpoint.
function getVisiblePropertiesWithCategories($itemTypeID, $clientID, $visiblePropertyIDs)
{
  $propertyKeys = array();

  foreach ($visiblePropertyIDs as $property) {
    $propertyID = intval($property['ID'] ?? 0);
    if ($propertyID > 0) {
      $propertyKeys[(string)$propertyID] = (string)($property['name'] ?? $propertyID);
    }
  }

  if (empty($propertyKeys)) {
    return array();
  }

  $query = 'SELECT rs_categories.RS_NAME AS "Category",
                   rs_item_properties.RS_PROPERTY_ID AS "propertyID"
            FROM rs_categories
            INNER JOIN rs_item_properties
              ON rs_categories.RS_CLIENT_ID = rs_item_properties.RS_CLIENT_ID
             AND rs_categories.RS_CATEGORY_ID = rs_item_properties.RS_CATEGORY_ID
            WHERE rs_categories.RS_CLIENT_ID = ' . intval($clientID) . '
              AND rs_categories.RS_ITEMTYPE_ID = ' . intval($itemTypeID) . '
              AND rs_item_properties.RS_PROPERTY_ID IN (' . implode(',', array_keys($propertyKeys)) . ')
            ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER';

  $queryResult = RSQuery($query);
  $results = array();

  if ($queryResult) {
    while ($row = $queryResult->fetch_assoc()) {
      $propertyID = (string)$row['propertyID'];
      $results[] = array(
        'Category' => html_entity_decode($row['Category'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'propertyID' => $propertyID,
        'propertyKey' => $propertyKeys[$propertyID]
      );
    }
  }

  return $results;
}

function parseProperyListValue($value, $clientID)
{
  if (!is_string($value)) {
    return $value;
  }

  $appListValueID = getAppListValueID($value);
  if ($appListValueID != '' && $appListValueID != '0') {
    $clientListValueID = getClientListValueID_RelatedWith($appListValueID, $clientID);

    if ($clientListValueID != '' && $clientListValueID != '0') {
      return getValue($clientListValueID, $clientID);
    }
  }

  return replaceUtf8Characters($value);
}
