<?php
//***************************************************************************************
// Description:
//    Get one, multiple or all item types and its associated propertyIDS + NAME
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
//    - Use the endpoint without body to obtain all of them
//
//  EXAMPLE 2:
//    {
//      "IDs": ["6","7","8"]
//    }
//
//  EXAMPLE 3:
//    {
//      "IDs": ["tasks","worksessions"],
//      "includeCategories": true
//    }
//***************************************************************************************
require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
handleApiCorsPreflight('GET');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../../utilities/RSMlistsManagement.php";

// Verify if the request has a body and validate its content
$contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength !== 0) {
    $requestBody = getRequestBody();
    verifyBodyContent($requestBody);
}

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();
$includeCategories = isset($requestBody->includeCategories) && $requestBody->includeCategories;

// Check if there is a request body sent
if (!isset($requestBody) || empty($requestBody)) {
    $itemTypeIDs = array_column(getClientItemTypes($clientID, '', false), "ID");
} else {
    $itemTypeIDs = $requestBody->IDs;
}

$responseArray = array();

foreach ($itemTypeIDs as $itemTypeID) {
    $itemTypeID = ParseITID($itemTypeID, $clientID);

    if ($itemTypeID == '' || $itemTypeID == '0') {
        continue;
    }

    $combinedArray = array();

    // Get properties associated with the current ItemTypeID
    $properties = getClientItemTypeProperties($itemTypeID, $clientID);
    $propertiesArray = array();
    $propertiesTypesArray = array();
    $propertiesListsArray = array();
    $propertiesCategoriesArray = array();
    $propertyCategories = $includeCategories
        ? getItemTypePropertyCategories($itemTypeID, $clientID)
        : array();
    $visibleCategoryIDs = array();
    $categoriesArray = array();

    if ($includeCategories) {
        foreach (getClientItemTypeCategories($itemTypeID, $clientID) as $category) {
            $categoriesArray[(string)$category['id']] = array(
                'id' => $category['id'],
                'name' => html_entity_decode($category['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            );
        }
    }

    // Loop through each property
    foreach ($properties as $property) {
        // Check if user has read permission of the property
        if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
            // Return the application's property name when the property is related.
            // Keep the numeric ID as a fallback for client properties without a relationship.
            $propertyKey = getAppPropertyName_RelatedWith($property['id'], $clientID);
            if ($propertyKey === '') {
                $propertyKey = $property['id'];
            }

            // Names can be stored HTML-encoded (e.g. &amp;, &#39;). Decode to real UTF-8 characters for the API response.
            $propertiesArray[$propertyKey] = html_entity_decode($property['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($includeCategories && isset($propertyCategories[(string)$property['id']])) {
                $category = $propertyCategories[(string)$property['id']];
                $propertiesCategoriesArray[$propertyKey] = $category['name'];
                $visibleCategoryIDs[(string)$category['id']] = true;
            }

            if ($property['type'] == 'identifier' || $property['type'] == 'identifiers') {
                $referredItemTypeID = getClientPropertyReferredItemType($property['id'], $clientID);
                $propertiesTypesArray[$propertyKey] = $property['type'] . (!empty($referredItemTypeID) ? ' ' . $referredItemTypeID : '');
            } else {
                $propertiesTypesArray[$propertyKey] = $property['type'];
            }

            if ($list = getPropertyList($property['id'], $clientID)) {
                $propertiesListsArray[$propertyKey] = array(
                    'listID' => $list['listID'],
                    'multiValues' => $list['multiValues'],
                    'values' => array(),
                );

                $listValues = getListValues($list['listID'], $clientID);
                foreach ($listValues as $value) {
                    $value['value'] = html_entity_decode($value['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $propertiesListsArray[$propertyKey]['values'][] = $value;
                }
            }
        }
    }

    if ($includeCategories) {
        // Only expose categories containing at least one property visible to the caller.
        $categoriesArray = array_values(array_intersect_key($categoriesArray, $visibleCategoryIDs));
    }

    // Only send item types when the user has permissions to see properties.
    if (!empty($propertiesArray)) {
        // Get the name of the ItemTypeID
        $itemTypeIDName = getClientItemTypeName($itemTypeID, $clientID);
        // Get the icon of the ItemTypeID
        $itemTypeIDIcon = getClientItemTypeIcon($itemTypeID, $clientID);

        // Add the itemTypeID and the name to the array.
        $combinedArray['itemTypeID'] = $itemTypeID;
        $combinedArray['name'] = html_entity_decode($itemTypeIDName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        //Add the properties to the array
        $combinedArray['properties'] = $propertiesArray;
        $combinedArray['propertyTypes'] = $propertiesTypesArray;
        $combinedArray['propertyLists'] = $propertiesListsArray;
        if ($includeCategories) {
            $combinedArray['categories'] = $categoriesArray;
            $combinedArray['propertyCategories'] = $propertiesCategoriesArray;
        }
        $combinedArray['icon'] = base64_encode(hex2bin($itemTypeIDIcon));

        array_push($responseArray, $combinedArray);
    }
}

if (!empty($responseArray)) {
    $json = json_encode($responseArray, JSON_UNESCAPED_UNICODE);
    returnJsonResponse($json);
} else {
    returnJsonMessage(200, '{}');
}

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkIsArray($body->IDs);
}

// Return the category metadata indexed by property ID for the requested item type.
// Visibility is applied later together with the rest of the property metadata.
function getItemTypePropertyCategories($itemTypeID, $clientID)
{
    $query = 'SELECT rs_item_properties.RS_PROPERTY_ID AS "propertyID",
                     rs_categories.RS_CATEGORY_ID AS "categoryID",
                     rs_categories.RS_NAME AS "categoryName"
              FROM rs_categories
              INNER JOIN rs_item_properties
                ON rs_categories.RS_CLIENT_ID = rs_item_properties.RS_CLIENT_ID
               AND rs_categories.RS_CATEGORY_ID = rs_item_properties.RS_CATEGORY_ID
              WHERE rs_categories.RS_CLIENT_ID = ' . intval($clientID) . '
                AND rs_categories.RS_ITEMTYPE_ID = ' . intval($itemTypeID);

    $queryResult = RSQuery($query);
    $categories = array();

    if ($queryResult) {
        while ($row = $queryResult->fetch_assoc()) {
            $categories[(string)$row['propertyID']] = array(
                'id' => $row['categoryID'],
                'name' => html_entity_decode(
                    $row['categoryName'],
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ),
            );
        }
    }

    return $categories;
}
