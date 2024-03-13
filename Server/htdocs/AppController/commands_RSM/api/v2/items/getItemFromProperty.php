<?php
//****************************************************************************************
//Description:
//    This endpoint is used to retrieve the parent item of another item through one of the son's identifier
//    (or multi-identifier) properties that must be specified when calling the endpoint.
//
//  REQUEST BODY
//  {
//    itemTypeID: itemTypeID to retrieve (for example: the itemTypeID of crm-accounts)
//    filterPropertyID: property of another itemTypeID related with the first one (for example: the property 'client' into invoices)
//    filterItemID: itemID of the filter property (for example: The identifier of the invoice from which we get the client)
//    translateIDs: (optional) If true, the response will have translatedIDs
//    includeCategories: (optional) If true, the response will show the different categories
// EXAMPLE:
//     {
//         "itemTypeID": "154",
//         "filterPropertyID": "1474",
//         "filterItemID": "14"
//     }
// RESPONSE:
// [
//     {
//         "ID": "11",
//         "Nombre": "Testing"
//     },
//     {
//         "ID": "12",
//         "Nombre": "Frontend"
//     },
//     {
//         "ID": "13",
//         "Nombre": "Backend"
//     },
//     {
//         "ID": "14",
//         "Nombre": "Serenity"
//     },
//     {
//         "ID": "15",
//         "Nombre": "API"
//     }
// ]
//}
//****************************************************************************************

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
$RStoken = getRStoken();
$RSuserID = getRSuserID();

$itemTypeID = $requestBody->itemTypeID;
$filterPropertyID = $requestBody->filterPropertyID;
$filterItemID = $requestBody->filterItemID;

// translateIDs
$translateIDs = false;
if (isset($requestBody->translateIDs) && $requestBody->includeCategories) {
    $translateIDs = true;
}

$properties   = array();
$attributes   = array();
$results      = array();

// get type of the filterPropertyID
$propertyType = getPropertyType($filterPropertyID, $clientID);

// if filterPropertyID is unsupported type return empty response
if (!isSingleIdentifier($propertyType) && !isMultiIdentifier($propertyType)) {

    if ($RSallowDebug) {
        returnJsonMessage(200, 'filterPropertyID must be either a single identifier or a multi identifier.');
    } else {
        returnJsonMessage(200, '');
    }
}

// Get itemTypeID of the filter property
$filteritemTypeID = getitemTypeIDFromProperties(array($filterPropertyID), $clientID);

// verify if item exists
if (!verifyItemExists($filterItemID, $filteritemTypeID, $clientID)) {
    if ($RSallowDebug) {
        returnJsonMessage(200, 'Source item with ID ' . $filterItemID . ' does not exist');
    } else {
        returnJsonMessage(200, '');
    }
}

// Get the value of the property $filterItemID for the given $filterPropertyID
$itemIDsToRetrieve = getItemPropertyValue($filterItemID, $filterPropertyID, $clientID);

// get the properties of the itemTypeID
$properties = getClientitemTypeProperties($itemTypeID, $clientID);

// verify how many items are related
if (strpos($itemIDsToRetrieve, ',') === false) {
    // single item identified, return it as usual for backwards compatibility

    foreach ($properties as $property) {
        if (RShasTokenPermission($RStoken, $property['id'], 'READ') && (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
            $value = getItemDataPropertyValue($itemIDsToRetrieve, $property['id'], $clientID);

            if (($property['type'] == 'image') || ($property['type'] == 'file')) {
                // A file needs additional properties like the file name and the file size, so let's query the database for extra attributes
                $attributes = explode(':', getItemPropertyValue($itemIDsToRetrieve, $property['id'], $clientID));

                $results[] = array(
                    'propertyID' => $property['id'],
                    'name' => html_entity_decode($property['name'], ENT_COMPAT, 'UTF-8'),
                    'value' => $value,
                    'type' => $property['type'],
                    'filename' => array_key_exists(0, $attributes) ? $attributes[0] : '',
                    'filesize' => array_key_exists(1, $attributes) ? $attributes[1] : ''
                );
            } elseif ($translateIDs && $property['type'] == 'identifier') {
                $results[] = array(
                    'propertyID' => $property['id'],
                    'name' => html_entity_decode($property['name'], ENT_COMPAT, 'UTF-8'),
                    'value' => $value,
                    'type' => $property['type'],
                    'trs' => base64_encode(getMainPropertyValue(getClientPropertyReferreditemType($property['id'], $clientID), $value, $clientID))
                );
            } elseif ($translateIDs && $property['type'] == 'identifiers') {
                $IDs = explode(',', $value);
                $trsProperties = '';
                $relateditemTypeID = getClientPropertyReferreditemType($property['id'], $clientID);

                foreach ($IDs as $id) {
                    $trsProperties .= base64_encode(getMainPropertyValue($relateditemTypeID, $value, $clientID)) . ',';
                }

                $results[] = array(
                    'propertyID' => $property['id'],
                    'name'  => html_entity_decode($property['name'], ENT_COMPAT, 'UTF-8'),
                    'value' => $value,
                    'type'  => $property['type'],
                    'trs'   => rtrim($trsProperties, ',')
                );
            } else {
                $results[] = array(
                    'propertyID' => $property['id'],
                    'name'  => html_entity_decode($property['name'], ENT_COMPAT, 'UTF-8'),
                    'value' => html_entity_decode($value, ENT_COMPAT | ENT_QUOTES, 'UTF-8'),
                    'type'  => $property['type']
                );
            }
        }
    }
} else {
    //multiple items, use getFilteredItemsIDs to return all

    // Check if user has permissions to read properties of the item and remove otherwise
    foreach ($properties as $key => $property) {
        // fix the id vs ID key issue TODO: review all code and solve it
        $properties[$key]['ID'] = $property['id'];
        $properties[$key]['name'] = html_entity_decode($property['name'], ENT_COMPAT, 'UTF-8');
        if (!RShasTokenPermission($RStoken, $property['id'], 'READ') && (!isPropertyVisible($RSuserID, $property['id'], $clientID))) {
            unset($properties[$key]);
        }
    }

    //check at least one property allowed and exit otherwise
    if (empty($properties)) {
        if ($RSallowDebug) {
            returnJsonMessage(403, 'No permissions to read these items');
        } else {
            returnJsonMessage(403, '');
        }
    }
    // get the items
    $itemsArray = getFilteredItemsIDs($itemTypeID, $clientID, array(), $properties, '', $translateIDs, '', $itemIDsToRetrieve, 'AND', 0, true, '', true);

    foreach ($itemsArray as $item) {
        foreach ($item as $propertyKey => $propertyValue) {
            $combinedArray[$propertyKey] = $propertyValue;
        }
        array_push($results, $combinedArray);
    }
}

if (!empty($results)) {
    $encodedResults = json_encode($results);
    returnJsonResponse($encodedResults);
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
    checkBodyContains($body, 'itemTypeID');
    checkBodyContains($body, 'filterPropertyID');
    checkBodyContains($body, 'filterItemID');
}
