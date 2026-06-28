<?php
header('Access-Control-Allow-Origin: *');
//***************************************************************************************
// Description:
//    Returns the tree contents for one or multiple root items.
//
// REQUEST BODY (JSON OBJECT):
// {
//   "roots": [
//     { "itemTypeID": "projects" }
//   ],
//   "allowedItemTypeIDs": ["projects", "tasks", "subtasks"],
//   "filterID": "0",
//   "fastFilter": "search text",
//   "returnOrder": true
// }
// fastFilter is plain text in this JSON API. Do not base64 encode it.
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
    $parentID = '0';

    if ($parentItemTypeID <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid root itemTypeID: ' . $root->itemTypeID) : returnJsonMessage(400, '');
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
    $isCustomerScopedToken = RSisCustomerScopedToken($RStoken);

    $pathProperties = array();
    $pathOrders = array();
    $mainProperties = array();
    $mainPropertyNames = array();

    foreach ($destinationItemTypes as $destinationItemTypeID) {
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

        $filteredItems = filterItemsForToken($clientID, $RStoken, $destinationItemTypeID, $filterID, $fastFilter, $returnOrder);

        foreach ($filteredItems as $filteredItem) {
            if ($isCustomerScopedToken && !RSitemMatchesTokenCustomerScope($RStoken, $clientID, $destinationItemTypeID, $filteredItem['ID'])) {
                continue;
            }

            if (isset($filteredItem['MAINPROP']) && $filteredItem['MAINPROP'] !== '') {
                if (!isset($mainProperties[$destinationItemTypeID]) || !is_array($mainProperties[$destinationItemTypeID])) {
                    $mainProperties[$destinationItemTypeID] = array();
                }

                $mainProperties[$destinationItemTypeID][$filteredItem['ID']] = $filteredItem['MAINPROP'];
                $mainPropertyNames[buildTreeNodeKey($destinationItemTypeID, $filteredItem['ID'])] = $filteredItem['MAINPROP'];
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

        $nodeKey = buildTreeNodeKey($row['nodeItemType'], $row['nodeID']);
        if ((!isset($row['name']) || $row['name'] === '') && isset($mainPropertyNames[$nodeKey])) {
            $results[$idx]['name'] = $mainPropertyNames[$nodeKey];
        }
    }

    return $results;
}

function filterItemsForToken($clientID, $RStoken, $itemTypeID, $filterID, $fastFilter = '', $returnOrder = 0, $mainPropName = 'MAINPROP')
{
    if ($fastFilter == '') {
        return filterItems($clientID, $itemTypeID, $filterID, $fastFilter, $returnOrder, $mainPropName);
    }

    $ids = getFastFilterItemIDsForToken($clientID, $RStoken, $itemTypeID, $fastFilter);
    if (empty($ids)) {
        return array();
    }

    $filterProperties = array();
    $returnProperties = array();
    $operator = '';

    $mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
    $returnProperties[] = array('ID' => $mainPropertyID, 'name' => $mainPropName);

    if ($filterID > 0) {
        $clauses = getFilterClauses($clientID, $filterID);
        $properties = getFilterProperties($clientID, $filterID);

        $result = RSquery('SELECT `RS_OPERATOR` FROM `rs_item_type_filters` WHERE `RS_CLIENT_ID`="' . $clientID . '" AND `RS_FILTER_ID`="' . $filterID . '"');
        if ($result && $result->num_rows == 1) {
            $res = $result->fetch_assoc();
            $operator = $res['RS_OPERATOR'];
        } else {
            $operator = 'AND';
        }

        foreach ($properties as $property) {
            $returnProperties[] = array('ID' => $property['conditionPropertyID'], 'name' => getClientPropertyName($property['conditionPropertyID'], $clientID));
        }

        foreach ($clauses as $clause) {
            $filterProperties[] = array('ID' => $clause['conditionPropertyID'], 'value' => $clause['conditionValue'], 'mode' => $clause['conditionOperator']);
        }
    }

    return getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true, '', implode(',', $ids), $operator, $returnOrder);
}

function getFastFilterItemIDsForToken($clientID, $RStoken, $itemTypeID, $fastFilter)
{
    $fastFilterArr = preg_split('/\s+/', trim($fastFilter));
    $idsForFilter = array();

    foreach ($fastFilterArr as $fastFilterVal) {
        if (normaliza(html_entity_decode($fastFilterVal, ENT_COMPAT, 'UTF-8')) != '') {
            $idsForFilter[$fastFilterVal] = array(-1);
        }
    }

    if (empty($idsForFilter)) {
        return array();
    }

    $propertyIDs = array();
    foreach (getClientItemTypePropertiesId($itemTypeID, $clientID) as $propertyID) {
        $propertyIDs[] = array('ID' => $propertyID);
    }

    $properties = getPropertiesExtendedForToken($itemTypeID, $RStoken, $propertyIDs);
    foreach ($properties as $property) {
        $propertyID = $property['propertyID'];
        $propertyType = getPropertyType($propertyID, $clientID);
        if ($propertyType == 'file' || $propertyType == 'image') {
            continue;
        }

        $propertyRows = getItemsPropertyValues($propertyID, $clientID, '', $propertyType, $itemTypeID, true);
        foreach ($propertyRows as $propertyItemID => $propertyRowValue) {
            foreach ($idsForFilter as $fastFilterVal => $ids) {
                if (mb_stripos(normaliza(html_entity_decode($propertyRowValue, ENT_COMPAT, 'UTF-8')), normaliza(html_entity_decode($fastFilterVal, ENT_COMPAT, 'UTF-8'))) !== false && array_search($propertyItemID, $idsForFilter[$fastFilterVal]) === false) {
                    $idsForFilter[$fastFilterVal][] = $propertyItemID;
                }
            }
        }
    }

    if (count($idsForFilter) > 1) {
        $numericIdsForFilter = array_map('array_values', $idsForFilter);
        $ids = array_shift($numericIdsForFilter);

        foreach ($numericIdsForFilter as $filterArray) {
            $ids = array_intersect($ids, $filterArray);
        }

        return $ids;
    }

    return reset($idsForFilter);
}

function buildRootNode($clientID, $parentItemTypeID, $parentID, $flatItems, $returnOrder)
{
    $rootNode = array(
        'itemTypeID' => (string) $parentItemTypeID,
        'children' => array()
    );

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
