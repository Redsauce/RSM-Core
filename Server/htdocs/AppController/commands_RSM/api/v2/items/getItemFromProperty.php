<?php
//****************************************************************************************
//Description:
//    Retrieves an item of the specified itemType with the associated values
//
//  REQUEST BODY 
//  {
//    itemType: itemType to retrieve (for example: the itemType of crm-accounts)
//    filterProperty: property of another itemType related with the first one (for example: the property 'client' into invoices)
//    filterPropertyID: itemID of the filter property (for example: The identifier of the invoice from which we get the client)
//  }
//****************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../api_headers.php";

// Definitions
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

$itemType = $requestBody->itemType;
$filterProperty   = $requestBody->filterProperty;
$filterPropertyID = $requestBody->filterPropertyID;

//translateIDs
$translateIDs = false;
if (isset($requestBody->translateIDs) && $requestBody->includeCategories == true) $translateIDs = true;

$properties   = array();
$attributes   = array();
$results      = array();

// get type of the filterProperty
$propertyType = getPropertyType($filterProperty, $clientID);

// if filterProperty is unsupported type return empty response
if (!isSingleIdentifier($propertyType) && !isMultiIdentifier($propertyType)) {
    returnJsonMessage(200, "");
}

// Get itemType of the filter property
$filterItemType = getItemTypeIDFromProperties(array($filterProperty), $clientID);

// verify if item exists 

if (!verifyItemExists($filterPropertyID, $filterItemType, $clientID)) {
    if ($RSallowDebug) returnJsonMessage(404, "Item doesn't exist");
    else returnJsonMessage(404, "");
}

// Get the value of the property $filterPropertyID for the given $filterProperty
$valuePropertyRelated = getItemPropertyValue($filterPropertyID, $filterProperty, $clientID);

// get the properties of the itemType
$properties = getClientItemTypeProperties($itemType, $clientID);

// verify how many items are

if (strpos($valuePropertyRelated, ",") === false) {
    //single item identified, return it as usual for backwards compatibility

    foreach ($properties as $property) {
        $value = getItemDataPropertyValue($valuePropertyRelated, $property['id'], $clientID);

        if (($property['type'] == 'image') || ($property['type'] == 'file')) {
            // A file needs additional properties like the file name and the file size, so let's query the database for extra attributes
            $attributes = explode(":", getItemPropertyValue($valuePropertyRelated, $property['id'], $clientID));

            $results[] = array(
                'propertyID' => $property['id'],
                'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => $value,
                'type' => $property['type'],
                'filename' => array_key_exists(0, $attributes) ? $attributes[0] : '',
                'filesize' => array_key_exists(1, $attributes) ? $attributes[1] : ''
            );
        } elseif ($translateIDs && $property['type'] == 'identifier') {
            $results[] = array(
                'propertyID' => $property['id'],
                'name' => html_entity_decode($property['name'],  ENT_COMPAT, "UTF-8"),
                'value' => $value,
                'type' => $property['type'],
                'trs' => base64_encode(getMainPropertyValue(getClientPropertyReferredItemType($property['id'], $clientID), $value, $clientID))
            );
        } elseif ($translateIDs && $property['type'] == 'identifiers') {
            $IDs = explode(",", $value);
            $trsProperties = '';
            $relatedItemType = getClientPropertyReferredItemType($property['id'], $clientID);

            foreach ($IDs as $id) {
                $trsProperties .= base64_encode(getMainPropertyValue($relatedItemType, $value, $clientID)) . ",";
            }

            $results[] = array(
                'propertyID'    => $property['id'],
                'name'  => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => $value,
                'type'  => $property['type'],
                'trs'   => rtrim($trsProperties, ",")
            );
        } else {
            $results[] = array(
                'propertyID'    => $property['id'],
                'name'  => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => html_entity_decode($value, ENT_COMPAT | ENT_QUOTES, "UTF-8"),
                'type'  => $property['type']
            );
        }
    }
} else {
    //multiple items, use getFilteredItemsIDs to return all

    // Check if user has permissions to read properties of the item and remove otherwise
    foreach ($properties as $key => $property) {
        // fix the id vs ID key issue TODO: review all code and solve it
        $properties[$key]['ID'] = $property['id'];
        $properties[$key]['name'] = html_entity_decode($property['name'], ENT_COMPAT, "UTF-8");
        if (!RShasTokenPermission($RStoken, $property['id'], "READ") && (!isPropertyVisible($RSuserID, $property['id'], $clientID))) {
            unset($properties[$key]);
        }
    }

    //check at least one property allowed and exit otherwise
    if (count($properties) == 0) {
        if ($RSallowDebug) returnJsonMessage(403, "No permissions to read these items");
        else returnJsonMessage(403, "");
    }
    // get the items
    $itemsArray = getFilteredItemsIDs($itemType, $clientID, array(), $properties, '', $translateIDs, '', $valuePropertyRelated, 'AND', 0, true, '', true);

    foreach ($itemsArray as $item) {
        foreach ($item as $propertyKey => $propertyValue) {
            $combinedArray[$propertyKey] = $propertyValue;
        }
        array_push($results, $combinedArray);
    }
}
$results = json_encode($results);
if ($results != "[]") {
    returnJsonResponse($results);
} else  returnJsonMessage(404, "");

function verifyBodyContent($body)
{
    checkBodyIsJsonObject($body);
    checkBodyContainsItemTypeFilterPropertyFilterPropertyID($body);
}
