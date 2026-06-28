<?php
header('Access-Control-Allow-Origin: *');
//***************************************************************************************
// Description:
//    Returns the tree contents for one or multiple root items.
//
// REQUEST BODY (JSON OBJECT):
// {
//   "roots": [
//     { "itemTypeID": "projects", "ID": "123" }
//   ],
//   "allowedItemTypeIDs": ["projects", "tasks", "subtasks"],
//   "filterID": "0",
//   "fastFilter": "",
//   "returnOrder": true
// }
//***************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../../utilities/RSMfiltersManagement.php';
require_once '../../../utilities/RSMlistsManagement.php';

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

$allowedItemTypes = normalizeItemTypeIDs($requestBody->allowedItemTypeIDs, $clientID);
$filterID         = isset($requestBody->filterID) && $requestBody->filterID !== '' ? $requestBody->filterID : '0';
$fastFilter       = isset($requestBody->fastFilter) ? $requestBody->fastFilter : '';
$returnOrder      = !empty($requestBody->returnOrder) ? 1 : 0;

if (empty($allowedItemTypes)) {
    $RSallowDebug ? returnJsonMessage(400, 'allowedItemTypeIDs must contain at least one valid itemTypeID') : returnJsonMessage(400, '');
}

$destinationItemTypes = getDestinationItemTypes($clientID, $filterID, $allowedItemTypes);
$flatResults = array();
$rootNodes = array();

foreach ($requestBody->roots as $root) {
    $parentItemTypeID = parseITID($root->itemTypeID, $clientID);
    $parentID = isset($root->ID) ? $root->ID : $root->itemID;

    if ($parentItemTypeID <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid root itemTypeID: ' . $root->itemTypeID) : returnJsonMessage(400, '');
    }

    if ($parentID !== '0' && $parentID !== 0) {
        if (!verifyItemExists($parentID, $parentItemTypeID, $clientID)) {
            $RSallowDebug ? returnJsonMessage(400, 'Root item does not exist: ' . $parentID) : returnJsonMessage(400, '');
        }

        if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $parentItemTypeID, $parentID)) {
            $RSallowDebug ? returnJsonMessage(403, 'Token customer scope does not allow access to root item: ' . $parentID) : returnJsonMessage(403, '');
        }
    }

    if (!canReadItemTypeMainProperty($RStoken, $RSuserID, $parentItemTypeID, $clientID)) {
        $RSallowDebug ? returnJsonMessage(403, 'No READ permission for root itemType main property: ' . $parentItemTypeID) : returnJsonMessage(403, '');
    }

    $rootFlatResults = getTreeFlatItems(
        $clientID,
        $RStoken,
        $parentItemTypeID,
        $parentID,
        $allowedItemTypes,
        $destinationItemTypes,
        $filterID,
        $fastFilter,
        $returnOrder
    );

    $flatResults = combineItemPaths($flatResults, $rootFlatResults);
    $rootNodes[] = buildRootNode($clientID, $parentItemTypeID, $parentID, $rootFlatResults, $returnOrder);
}

$responseArray = array(
    'result' => 'OK',
    'filteredItemTypeIDs' => $destinationItemTypes,
    'roots' => $rootNodes,
    'items' => $flatResults
);

returnJsonResponse(json_encode($responseArray, JSON_UNESCAPED_UNICODE));

function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContains($body, 'roots');
    checkBodyContains($body, 'allowedItemTypeIDs');
    checkIsArray($body->roots);
    checkIsArray($body->allowedItemTypeIDs);

    foreach ($body->roots as $root) {
        checkIsJsonObject($root);
        checkBodyContains($root, 'itemTypeID');

        if (!isset($root->ID) && !isset($root->itemID)) {
            global $RSallowDebug;
            $RSallowDebug ? returnJsonMessage(400, "Root must contain 'ID' or 'itemID'") : returnJsonMessage(400, '');
        }
    }
}

function normalizeItemTypeIDs($itemTypeIDs, $clientID)
{
    $normalizedItemTypeIDs = array();

    foreach ($itemTypeIDs as $itemTypeID) {
        $parsedItemTypeID = parseITID($itemTypeID, $clientID);

        if ($parsedItemTypeID > 0 && !in_array($parsedItemTypeID, $normalizedItemTypeIDs)) {
            $normalizedItemTypeIDs[] = $parsedItemTypeID;
        }
    }

    return $normalizedItemTypeIDs;
}

function canReadItemTypeMainProperty($RStoken, $RSuserID, $itemTypeID, $clientID)
{
    $mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

    return RShasTokenPermission($RStoken, $mainPropertyID, 'READ') || isPropertyVisible($RSuserID, $mainPropertyID, $clientID);
}

function getDestinationItemTypes($clientID, $filterID, $allowedItemTypes)
{
    global $RSallowDebug;

    if ($filterID == '0') {
        return $allowedItemTypes;
    }

    $filterItemTypeID = getFilterItemType($clientID, $filterID);
    if ($filterItemTypeID <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid filterID: ' . $filterID) : returnJsonMessage(400, '');
    }

    if (!in_array($filterItemTypeID, $allowedItemTypes)) {
        $RSallowDebug ? returnJsonMessage(400, 'Filter itemType is not included in allowedItemTypeIDs') : returnJsonMessage(400, '');
    }

    return array($filterItemTypeID);
}

function getTreeFlatItems($clientID, $RStoken, $parentItemTypeID, $parentID, $allowedItemTypes, $destinationItemTypes, $filterID, $fastFilter, $returnOrder)
{
    $results = array();
    $parentItemTypeMainPropertyID = getMainPropertyID($parentItemTypeID, $clientID);
    $parentItemTypeMainPropertyType = getPropertyType($parentItemTypeMainPropertyID, $clientID);

    $pathProperties = array();
    $pathOrders = array();
    $mainProperties = array();

    foreach ($destinationItemTypes as $destinationItemTypeID) {
        if (!canReadItemTypeMainProperty($RStoken, getRSuserID(), $destinationItemTypeID, $clientID)) {
            continue;
        }

        $treePath = array();
        getTreePath(
            $clientID,
            $treePath,
            array(array(
                'itemTypeID' => $parentItemTypeID,
                'mainPropertyID' => $parentItemTypeMainPropertyID,
                'mainPropertyType' => $parentItemTypeMainPropertyType
            )),
            $destinationItemTypeID,
            $allowedItemTypes,
            10
        );

        foreach ($treePath as $path) {
            foreach ($path as $step) {
                if (!array_key_exists($step['mainPropertyID'], $pathProperties)) {
                    $mainProperties[$step['itemTypeID']] = getItemsPropertyValues($step['mainPropertyID'], $clientID, '', $step['mainPropertyType'], $step['itemTypeID']);
                }

                if (array_key_exists('propertyID', $step) && !array_key_exists($step['propertyID'], $pathProperties)) {
                    $orderArray = array();
                    $pathProperties[$step['propertyID']] = getItemsPropertyValues($step['propertyID'], $clientID, '', $step['propertyType'], $step['itemTypeID'], false, $returnOrder, $orderArray);

                    if ($returnOrder) {
                        $pathOrders[$step['propertyID']] = $orderArray;
                    }
                }

                if (array_key_exists('recursivePropertyID', $step) && !array_key_exists($step['recursivePropertyID'], $pathProperties)) {
                    $orderArray = array();
                    $pathProperties[$step['recursivePropertyID']] = getItemsPropertyValues($step['recursivePropertyID'], $clientID, '', '', $step['itemTypeID'], false, $returnOrder, $orderArray);

                    if ($returnOrder) {
                        $pathOrders[$step['recursivePropertyID']] = $orderArray;
                    }
                }
            }
        }

        $filteredItems = filterItems($clientID, $destinationItemTypeID, $filterID, $fastFilter, $returnOrder);

        foreach ($filteredItems as $filteredItem) {
            if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $destinationItemTypeID, $filteredItem['ID'])) {
                continue;
            }

            $additionalProps = '';
            foreach ($filteredItem as $property => $value) {
                if ($property != 'ID' && $property != 'MAINPROP' && $property != 'ITEM_ORDER') {
                    $additionalProps .= base64_encode($property) . ',' . base64_encode($value) . ';';
                }
            }

            $additionalProps = rtrim($additionalProps, ';');
            $tempPaths = getPathsForItem($clientID, $destinationItemTypeID, $filteredItem['ID'], $treePath, $parentID, $additionalProps, $pathProperties, $mainProperties, $returnOrder, $pathOrders);
            $results = combineItemPaths($results, $tempPaths);
        }
    }

    foreach ($results as $idx => $row) {
        if (!isset($row['nodeMainPropertyID'])) {
            $results[$idx]['nodeMainPropertyID'] = getMainPropertyID($row['nodeItemType'], $clientID);
        }
    }

    return $results;
}

function buildRootNode($clientID, $parentItemTypeID, $parentID, $flatItems, $returnOrder)
{
    $rootNode = array(
        'nodeID' => (string) $parentID,
        'nodeItemType' => (string) $parentItemTypeID,
        'nodeMainPropertyID' => getMainPropertyID($parentItemTypeID, $clientID),
        'name' => ($parentID === '0' || $parentID === 0) ? '' : html_entity_decode(getMainPropertyValue($parentItemTypeID, $parentID, $clientID), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'parentID' => '',
        'parentItemType' => '',
        'parentPropertyID' => '',
        'children' => array()
    );

    if ($returnOrder) {
        $rootNode['order'] = '0';
    }

    $childrenByParent = array();
    foreach ($flatItems as $item) {
        $parentKey = buildTreeNodeKey($item['parentItemType'], $item['parentID']);

        if (!isset($childrenByParent[$parentKey])) {
            $childrenByParent[$parentKey] = array();
        }

        $childrenByParent[$parentKey][] = $item;
    }

    $rootNode['children'] = buildChildrenForParent($childrenByParent, $parentItemTypeID, $parentID, array());

    return $rootNode;
}

function buildChildrenForParent($childrenByParent, $parentItemTypeID, $parentID, $visited)
{
    $parentKey = buildTreeNodeKey($parentItemTypeID, $parentID);

    if (!isset($childrenByParent[$parentKey])) {
        return array();
    }

    $children = array();
    foreach ($childrenByParent[$parentKey] as $item) {
        $nodeKey = buildTreeNodeKey($item['nodeItemType'], $item['nodeID']);
        if (in_array($nodeKey, $visited)) {
            continue;
        }

        $child = $item;
        unset($child['childs']);
        $child['name'] = isset($child['name']) ? html_entity_decode($child['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        $child['children'] = buildChildrenForParent($childrenByParent, $item['nodeItemType'], $item['nodeID'], array_merge($visited, array($nodeKey)));
        $children[] = $child;
    }

    usort($children, 'compareTreeNodes');

    return $children;
}

function buildTreeNodeKey($itemTypeID, $itemID)
{
    return (string) $itemTypeID . ':' . (string) $itemID;
}

function compareTreeNodes($left, $right)
{
    $leftOrder = isset($left['order']) && is_numeric($left['order']) ? intval($left['order']) : 0;
    $rightOrder = isset($right['order']) && is_numeric($right['order']) ? intval($right['order']) : 0;

    if ($leftOrder != $rightOrder) {
        return $leftOrder < $rightOrder ? -1 : 1;
    }

    return strcasecmp($left['name'], $right['name']);
}
