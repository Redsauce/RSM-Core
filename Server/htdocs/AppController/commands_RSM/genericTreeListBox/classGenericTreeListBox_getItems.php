<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";
require_once "../utilities/RSMlistsManagement.php";
require_once "../utilities/RStools.php";

// Parameters validation
isset($GLOBALS[$cstRS_POST]['clientID'          ]) ? $clientID          =               $GLOBALS[$cstRS_POST]['clientID'          ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['parentID'          ]) ? $parentID          =               $GLOBALS[$cstRS_POST]['parentID'          ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['parentItemTypeID'  ]) && $GLOBALS[$cstRS_POST]['parentItemTypeID'] != "" ? $parentItemTypeID = $GLOBALS[$cstRS_POST]['parentItemTypeID'] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['allowedItemTypeIDs']) ? $allowedItemTypes  = explode(",",  $GLOBALS[$cstRS_POST]['allowedItemTypeIDs']) : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['fastFilter'        ]) ? $fastFilter        = base64_decode($GLOBALS[$cstRS_POST]['fastFilter'        ]) : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['filterID'          ]) ? $filterID          =               $GLOBALS[$cstRS_POST]['filterID'          ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['extraFilter'       ]) ? $extraFilter       =               $GLOBALS[$cstRS_POST]['extraFilter'       ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['returnOrder'       ]) ? $returnOrder       =               $GLOBALS[$cstRS_POST]['returnOrder'       ]  : $returnOrder = 0;

$avoidChildsInRoot = isset($GLOBALS[$cstRS_POST]['avoidChildsInRoot' ]) ? true : false;

$parentItemTypeID = ParseITID($parentItemTypeID, $clientID);

if ($filterID == '') {
    $filterID = "0";
}

if ($returnOrder != 0) {
    $returnOrder = 1;
}

$extraFilters = array();
$extraFilters = explode(",", $extraFilter);

$results              = array();
$destinationItemTypes = array();
$recursiveParentValueCache = array();

if (($filterID == "0" && $fastFilter == '') || $parentID != "0") {
    // show only next level
    if (($avoidChildsInRoot) && ($parentID == "0")) {
    	// We are refreshing the root level, so only the parent is allowed
		$allowedItemTypes = array($parentItemTypeID);
	}
	$descendants = getDescendantsLevel($clientID, $parentItemTypeID, $allowedItemTypes);

    if ($parentID == 0 && array_search_ID($parentItemTypeID, $descendants, "itemTypeID") === false) {
        // getting root level from a not recursive itemtype, add to descendants
        array_unshift($descendants, array("itemTypeID" => $parentItemTypeID, "propertyType" => '', "propertyID" => '0'));
    }

    foreach ($descendants as $descendant) {
        $recursivePropertyPos = false;
        $subDescendants = array();
        if ((string) $parentID === '0') {
            // At the virtual root, items with a recursive parent belong below that parent.
            $subDescendants = getDescendantsLevel($clientID, $descendant['itemTypeID'], $allowedItemTypes);
            $recursivePropertyPos = array_search_ID($descendant['itemTypeID'], $subDescendants, "itemTypeID");
        }

        // build filter array
        $filterProperties = array();
        if ($descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
            if ($parentID == "0" || $parentID == "") {
                $filterProperties[] = array('ID' => $descendant['propertyID'], 'value' => "0", 'mode' => 'IN');
            } else
                $filterProperties[] = array('ID' => $descendant['propertyID'], 'value' => $parentID, 'mode' => 'IN');
        }

        // Add the extra filters if some of them apply to this item type
        if ($extraFilter != '') {
            $itemTypeProperties = getProperties($descendant['itemTypeID'], $clientID);

            foreach ($extraFilters as $filter) {
                $theProperties = array();
                $theProperties = explode(";", $filter);

                // Obtain the property IDsi
                $pID = parsePID($theProperties[0], $clientID);

                // Obtain the property value
                if (isBase64($theProperties[1])) {
                    // The user is specifying a custom base64 filter value
                    $pValue = base64_decode($theProperties[1]);
                } else {
                    // The value is not encoded in base64 so try to get a related property with the value
                    $pValue = getValue(getClientListValueID_RelatedWith(getAppListValueID($theProperties[1]), $clientID), $clientID);
                }

                if (in_array($pID, $itemTypeProperties)) {
                    $filterProperties[] = array('ID' => $pID, 'value' => $pValue, 'mode' => $theProperties[2]);
                }
            }
        }

        // compute main property ID once and reuse both for returnProperties
        $mainPropertyID = getMainPropertyID($descendant['itemTypeID'], $clientID);

        $returnProperties   = array();
        $returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'name');

        if ($returnOrder && $descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
            $returnProperties[] = array('ID' => $descendant['propertyID'], 'name' => 'parentID');
        }

        if ($recursivePropertyPos !== false) {
            $returnProperties[] = array('ID' => $subDescendants[$recursivePropertyPos]['propertyID'], 'name' => 'recursiveProperty');
        }

        // get items pertaining to the parent passed
        $auxArr = array();
        $result = IQ_getFilteredItemsIDs($descendant['itemTypeID'], $clientID, $filterProperties, $returnProperties,'','','',"AND",$auxArr,$returnOrder);

        if ($result) {
            while ($item = $result->fetch_assoc()) {

            // The relation used by the SQL filter is authoritative. A separate recursive relation
            // does not replace this direct parent relation, so the item must remain visible here.
            $includeItem = true;

            if ((string) $parentID === '0' && $recursivePropertyPos !== false) {
                $recursiveProperty = $subDescendants[$recursivePropertyPos];
                $resolveRecursiveParents = function ($itemID) use (&$recursiveParentValueCache, $clientID, $descendant, $recursiveProperty) {
                    $cacheKey = $descendant['itemTypeID'] . ':' . $recursiveProperty['propertyID'] . ':' . $itemID;
                    if (!array_key_exists($cacheKey, $recursiveParentValueCache)) {
                        $recursiveParentValueCache[$cacheKey] = getItemPropertyValue(
                            $itemID,
                            $recursiveProperty['propertyID'],
                            $clientID,
                            $recursiveProperty['propertyType'],
                            $descendant['itemTypeID']
                        );
                    }

                    return explode(',', (string) $recursiveParentValueCache[$cacheKey]);
                };

                $includeItem = shouldIncludeVirtualRootTreeItem(
                    $item['ID'],
                    isset($item['recursiveProperty']) ? $item['recursiveProperty'] : '',
                    $resolveRecursiveParents
                );
            }

            if ($includeItem && $descendant['itemTypeID'] == $parentItemTypeID) {
                $resolveRecursiveParents = function ($itemID) use (&$recursiveParentValueCache, $clientID, $descendant) {
                    $cacheKey = $descendant['itemTypeID'] . ':' . $descendant['propertyID'] . ':' . $itemID;
                    if (!array_key_exists($cacheKey, $recursiveParentValueCache)) {
                        $recursiveParentValueCache[$cacheKey] = getItemPropertyValue(
                            $itemID,
                            $descendant['propertyID'],
                            $clientID,
                            $descendant['propertyType'],
                            $descendant['itemTypeID']
                        );
                    }

                    return explode(',', (string) $recursiveParentValueCache[$cacheKey]);
                };

                // Lazy child requests do not contain their ancestor path. Omit every edge that
                // belongs to a cycle so expanding either side cannot recreate the same nodes.
                if (shouldSuppressDirectTreeCycleEdge($item['ID'], $parentID, $resolveRecursiveParents)) {
                    $includeItem = false;
                }
            }

            if ($includeItem) {
                $results[] = array(
                    "nodeID" => $item['ID'],
                    "nodeItemType" => $descendant['itemTypeID'],
                    "nodeMainPropertyID" => $mainPropertyID,
                    "name" => isset($item['name']) ? $item['name'] : '',
                    "parentID" => $parentID,
                    "parentItemType" => $parentItemTypeID,
                    "parentPropertyID" => $descendant['propertyID'],
                    "childs" => ''
                );
                if ($returnOrder) {
                    if ($descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
                        if(isset($item['parentID_ord']) && $item['parentID_ord'] != ''){
                            if (strpos($item['parentID'], ',') !== false) {
                                $orders = explode(',', $item['parentID_ord']);
                                $results[count($results)-1]["order"] = $orders[array_search($parentID, explode(',', $item['parentID']))];
                                if (!is_numeric($results[count($results)-1]["order"])) $results[count($results)-1]["order"] = "0";
                            } else {
                                $results[count($results)-1]["order"] = $item['parentID_ord'];
                            }

                        } else {
                            $results[count($results)-1]["order"] = "0";
                        }

                    } else {
                        if(isset($item['ITEM_ORDER']) && $item['ITEM_ORDER'] != ''){
                            $results[count($results)-1]["order"] = $item['ITEM_ORDER'];
                        } else {
                            $results[count($results)-1]["order"] = "0";
                        }

                    }
                }
            }
        }
        }
    }
} else {
    if ($filterID != "0") {

        //get filter itemtype
        $destinationItemTypes[] = getFilterItemType($clientID, $filterID);
        if ($destinationItemTypes[0] <= 0) {
            $results['result'] = "NOK";
            $results['description'] = "INVALID FILTER";
            RSReturnArrayResults($results);
            exit ;
        }
    } else {
		if (count($allowedItemTypes) > 0) {
            $destinationItemTypes = $allowedItemTypes;
        } else {
            //not allowed itemtypes, get all
            $theQuery = "SELECT `RS_ITEMTYPE_ID` as 'ID' FROM `rs_item_types` WHERE `RS_CLIENT_ID`='" . $clientID . "' ORDER BY `RS_ORDER`";

            // Query the database
            $res = RSquery($theQuery);

            if ($res) {
                while ($row = $res->fetch_assoc()) $destinationItemTypes[] = $row['ID'];
            }
        }
    }

    //get parent ItemType MainProperty ID and Type for treePath root level
    $parentItemTypeMainPropertyID   = getMainPropertyID($parentItemTypeID, $clientID);
    $parentItemTypeMainPropertyType = getPropertyType($parentItemTypeMainPropertyID, $clientID);

    $pathProperties = array();
    $pathOrders     = array();
    $mainProperties = array();

    foreach ($destinationItemTypes as $destinationItemTypeID) {
        $treePath = array();
        getTreePath($clientID, $treePath, array( array('itemTypeID' => $parentItemTypeID,'mainPropertyID'=>$parentItemTypeMainPropertyID,'mainPropertyType'=>$parentItemTypeMainPropertyType)), $destinationItemTypeID, $allowedItemTypes, 10);

        //get all properties needed
        foreach ($treePath as $path){
          foreach ($path as $step){
            if(!array_key_exists($step['mainPropertyID'],$pathProperties)){
                $mainProperties[$step['itemTypeID']] = getItemsPropertyValues($step['mainPropertyID'], $clientID, '', $step['mainPropertyType'], $step['itemTypeID']);
            }
            if(array_key_exists('propertyID',$step)&&!array_key_exists($step['propertyID'],$pathProperties)){
                $orderArray = array();
                $pathProperties[$step['propertyID']] = getItemsPropertyValues($step['propertyID'], $clientID, '', $step['propertyType'], $step['itemTypeID'], false, $returnOrder, $orderArray);
                if ($returnOrder) {
                    $pathOrders[$step['propertyID']] = $orderArray;
                }
            }
            if(array_key_exists('recursivePropertyID',$step)&&!array_key_exists($step['recursivePropertyID'], $pathProperties)){
                $orderArray = array();
                $pathProperties[$step['recursivePropertyID']] = getItemsPropertyValues($step['recursivePropertyID'], $clientID, '', '', $step['itemTypeID'], false, $returnOrder, $orderArray);
                if ($returnOrder) {
                    $pathOrders[$step['recursivePropertyID']] = $orderArray;
                }
            }
          }
        }

        //apply filter to itemtype
        $filteredItems = filterItems($clientID, $destinationItemTypeID, $filterID, $fastFilter, $returnOrder);

        //get path for item
        foreach ($filteredItems as $filteredItem) {
            $additionalProps = '';
            foreach ($filteredItem as $property => $value)
                if ($property != "ID" && $property != "MAINPROP" && $property != "ITEM_ORDER")
                    $additionalProps .= base64_encode($property) . "," . base64_encode($value) . ";";

            $additionalProps = rtrim($additionalProps, ";");
            $tempPaths = getPathsForItem($clientID, $destinationItemTypeID, $filteredItem['ID'], $treePath, $parentID, $additionalProps, $pathProperties, $mainProperties, $returnOrder, $pathOrders);
            $results = combineItemPaths($results, $tempPaths);
        }

    // ensure nodeMainPropertyID is included for every resulting node (especially when using filters/tree paths)
    if (!empty($results)) {
        foreach ($results as $idx => $row) {
            if (!isset($row['nodeMainPropertyID'])) {
                $results[$idx]['nodeMainPropertyID'] = getMainPropertyID($row['nodeItemType'], $clientID);
            }
        }
    }
    }

    // Combining paths from several matches can recreate an edge removed in each individual path.
    $results = removeDuplicateTreeResultNodes(removeCyclicTreeResultEdges($results));
}

function shouldSuppressDirectTreeCycleEdge($childItemID, $parentItemID, $resolveParents)
{
    $childItemID = trim((string) $childItemID);
    $parentItemID = trim((string) $parentItemID);

    if ($childItemID === '' || $parentItemID === '' || $parentItemID === '0') {
        return false;
    }

    if ($childItemID === $parentItemID) {
        return true;
    }

    $queue = array($parentItemID);
    $visitedItemIDs = array($parentItemID => true);
    $processedItems = 0;
    $queueIndex = 0;

    while (isset($queue[$queueIndex]) && $processedItems < 10000) {
        $currentItemID = $queue[$queueIndex];
        $queueIndex++;
        $processedItems++;

        foreach (call_user_func($resolveParents, $currentItemID) as $nextParentID) {
            $nextParentID = trim((string) $nextParentID);
            if ($nextParentID === '' || $nextParentID === '0') {
                continue;
            }

            if ($nextParentID === $childItemID) {
                return true;
            }

            if (!isset($visitedItemIDs[$nextParentID])) {
                $visitedItemIDs[$nextParentID] = true;
                $queue[] = $nextParentID;
            }
        }
    }

    return false;
}

function shouldIncludeVirtualRootTreeItem($itemID, $recursiveProperty, $resolveParents)
{
    $recursiveParentIDs = explode(',', (string) $recursiveProperty);

    foreach ($recursiveParentIDs as $recursiveParentID) {
        $recursiveParentID = trim((string) $recursiveParentID);
        if ($recursiveParentID === '' || $recursiveParentID === '0') {
            return true;
        }
    }

    // A cyclic component has no natural root. Keep its members visible at the virtual root;
    // their cyclic edges will be removed by the normal cycle handling.
    foreach ($recursiveParentIDs as $recursiveParentID) {
        if (shouldSuppressDirectTreeCycleEdge($itemID, $recursiveParentID, $resolveParents)) {
            return true;
        }
    }

    return false;
}

function removeCyclicTreeResultEdges($items)
{
    $parentsByItemType = array();

    foreach ($items as $item) {
        if (
            !isset($item['nodeID'], $item['nodeItemType'], $item['parentID'], $item['parentItemType'])
            || (string) $item['parentID'] === '0'
            || (string) $item['nodeItemType'] !== (string) $item['parentItemType']
        ) {
            continue;
        }

        $itemTypeID = (string) $item['nodeItemType'];
        $nodeID = (string) $item['nodeID'];
        $parentsByItemType[$itemTypeID][$nodeID][] = (string) $item['parentID'];
    }

    $filteredItems = array();
    foreach ($items as $item) {
        $isRecursiveEdge = isset($item['nodeID'], $item['nodeItemType'], $item['parentID'], $item['parentItemType'])
            && (string) $item['parentID'] !== '0'
            && (string) $item['nodeItemType'] === (string) $item['parentItemType'];

        if ($isRecursiveEdge) {
            $itemTypeID = (string) $item['nodeItemType'];
            $resolveParents = function ($itemID) use ($parentsByItemType, $itemTypeID) {
                return isset($parentsByItemType[$itemTypeID][(string) $itemID])
                    ? $parentsByItemType[$itemTypeID][(string) $itemID]
                    : array();
            };

            if (shouldSuppressDirectTreeCycleEdge($item['nodeID'], $item['parentID'], $resolveParents)) {
                // Keep the node but remove the cyclic relationship. This lets subsequent
                // deduplication represent every cycle member as a root-level sibling.
                $item['parentID'] = 0;
                $item['parentItemType'] = $item['nodeItemType'];
                $item['parentPropertyID'] = '';
            }
        }

        $filteredItems[] = $item;
    }

    return $filteredItems;
}

function removeDuplicateTreeResultNodes($items)
{
    $selectedItems = array();
    $itemOrder = array();

    foreach ($items as $index => $item) {
        if (!isset($item['nodeID'], $item['nodeItemType'])) {
            $key = 'unidentified:' . $index;
        } else {
            $key = (string) $item['nodeItemType'] . ':' . (string) $item['nodeID'];
        }

        if (!isset($selectedItems[$key])) {
            $selectedItems[$key] = $item;
            $itemOrder[] = $key;
            continue;
        }

        // A root occurrence is preferred because it cannot become orphaned when another duplicate
        // parent relation is removed. Otherwise retain the first stable path returned by the search.
        $selectedIsRoot = isset($selectedItems[$key]['parentID']) && (string) $selectedItems[$key]['parentID'] === '0';
        $candidateIsRoot = isset($item['parentID']) && (string) $item['parentID'] === '0';
        if (!$selectedIsRoot && $candidateIsRoot) {
            $selectedItems[$key] = $item;
        }
    }

    // Rebuild child references from the selected parent relation so removed duplicates cannot still
    // be rendered through a stale `childs` entry.
    foreach ($selectedItems as &$item) {
        if (isset($item['nodeID'])) {
            $item['childs'] = '';
        }
    }
    unset($item);

    foreach ($selectedItems as $item) {
        if (
            !isset($item['nodeID'], $item['nodeItemType'], $item['parentID'], $item['parentItemType'])
            || (string) $item['parentID'] === '0'
        ) {
            continue;
        }

        $parentKey = (string) $item['parentItemType'] . ':' . (string) $item['parentID'];
        if (!isset($selectedItems[$parentKey])) {
            continue;
        }

        $childReference = (string) $item['nodeID'] . ',' . (string) $item['nodeItemType'];
        $existingChildren = $selectedItems[$parentKey]['childs'] === ''
            ? array()
            : explode(';', $selectedItems[$parentKey]['childs']);
        if (!in_array($childReference, $existingChildren, true)) {
            $existingChildren[] = $childReference;
            $selectedItems[$parentKey]['childs'] = implode(';', $existingChildren);
        }
    }

    $deduplicatedItems = array();
    foreach ($itemOrder as $key) {
        $deduplicatedItems[] = $selectedItems[$key];
    }

    return $deduplicatedItems;
}

array_unshift($results, array("result" => "OK", "filteredID" => implode(",", $destinationItemTypes)));

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
