<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";
require_once "../utilities/RSMlistsManagement.php";
require_once "../utilities/RStools.php";

// Parameters validation
isset($GLOBALS['RS_POST']['clientID']) ? $clientID          =               $GLOBALS['RS_POST']['clientID']  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentID']) ? $parentID          =               $GLOBALS['RS_POST']['parentID']  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentItemTypeID']) && $GLOBALS['RS_POST']['parentItemTypeID'] != "" ? $parentItemTypeID = $GLOBALS['RS_POST']['parentItemTypeID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['allowedItemTypeIDs']) ? $allowedItemTypes  = explode(",", $GLOBALS['RS_POST']['allowedItemTypeIDs']) : dieWithError(400);
isset($GLOBALS['RS_POST']['fastFilter']) ? $fastFilter        = base64_decode($GLOBALS['RS_POST']['fastFilter']) : dieWithError(400);
isset($GLOBALS['RS_POST']['filterID']) ? $filterID          =               $GLOBALS['RS_POST']['filterID']  : dieWithError(400);
isset($GLOBALS['RS_POST']['extraFilter']) ? $extraFilter       =               $GLOBALS['RS_POST']['extraFilter']  : dieWithError(400);
isset($GLOBALS['RS_POST']['returnOrder']) ? $returnOrder       =               $GLOBALS['RS_POST']['returnOrder']  : $returnOrder = 0;

$avoidChildsInRoot = isset($GLOBALS['RS_POST']['avoidChildsInRoot']) ? true : false;

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

if (($filterID == "0" && $fastFilter == '') || $parentID != "0") {
    // show only next level
    if (($avoidChildsInRoot) && ($parentID == "0")) {
        // We are refreshing the root level, so only the parent is allowed
        $allowedItemTypes = array($parentItemTypeID);
    }
    $descendants = getDescendantsLevel($clientID, $parentItemTypeID, $allowedItemTypes);

    if ($parentID == 0 && arraySearchID($parentItemTypeID, $descendants, "itemTypeID") === false) {
        // getting root level from a not recursive itemtype, add to descendants
        array_unshift($descendants, array("itemTypeID" => $parentItemTypeID, "propertyType" => '', "propertyID" => '0'));
    }

    foreach ($descendants as $descendant) {
        // get all descendants of this itemtype
        $subDescendants = getDescendantsLevel($clientID, $descendant['itemTypeID'], $allowedItemTypes);

        // build filter array
        $filterProperties = array();
        if ($descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
            if ($parentID == "0" || $parentID == "") {
                $filterProperties[] = array('ID' => $descendant['propertyID'], 'value' => "0", 'mode' => 'IN');
            } else {
                $filterProperties[] = array('ID' => $descendant['propertyID'], 'value' => $parentID, 'mode' => 'IN');
            }
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
                    $pValue = getValue(getClientListValueIDRelatedWith(getAppListValueID($theProperties[1]), $clientID), $clientID);
                }

                if (in_array($pID, $itemTypeProperties)) {
                    $filterProperties[] = array('ID' => $pID, 'value' => $pValue, 'mode' => $theProperties[2]);
                }
            }
        }

        $returnProperties   = array();
        $returnProperties[] = array('ID' => getMainPropertyID($descendant['itemTypeID'], $clientID), 'name' => 'name');

        if ($returnOrder && $descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
            $returnProperties[] = array('ID' => $descendant['propertyID'], 'name' => 'parentID');
        }

        // check if is recursive itemtype and get recursive parent in this case
        $recursivePropertyPos = arraySearchID($descendant['itemTypeID'], $subDescendants, "itemTypeID");
        if ($recursivePropertyPos !== false) {
            $returnProperties[] = array('ID' => $subDescendants[$recursivePropertyPos]['propertyID'], 'name' => 'recursiveProperty');
        }

        // get items pertaining to the parent passed
        $auxArr = array();
        $result = iqGetFilteredItemsIDs($descendant['itemTypeID'], $clientID, $filterProperties, $returnProperties, '', '', '', "AND", $auxArr, $returnOrder);

        if ($result) {
            while ($item = $result->fetch_assoc()) {

                // check if it is a recursive itemtype and has a parent of its own itemtype in another branch (don't add in that case)
                if (!isset($item['recursiveProperty'])) {
                    $item['recursiveProperty'] = '';
                }

                // We must explode $item['recursiveProperty'] because it could be multiidentifier
                $recursiveProperties = explode(",", $item['recursiveProperty']);
                foreach ($recursiveProperties as $recursiveProperty) {
                    if ($recursiveProperty == '' || $recursiveProperty == "0" || ($recursiveProperty == $parentID && $descendant['itemTypeID'] == $parentItemTypeID)) {
                        $results[] = array("nodeID" => $item['ID'], "nodeItemType" => $descendant['itemTypeID'], "name" => isset($item['name']) ? $item['name'] : '', "parentID" => $parentID, "parentItemType" => $parentItemTypeID, "parentPropertyID" => $descendant['propertyID'], "childs" => '');
                        if ($returnOrder) {
                            if ($descendant['propertyID'] != "0" && $descendant['propertyID'] != "") {
                                if (isset($item['parentID_ord']) && $item['parentID_ord'] != '') {
                                    if (strpos($item['parentID'], ',') !== false) {
                                        $orders = explode(',', $item['parentID_ord']);
                                        $results[count($results) - 1]["order"] = $orders[array_search($parentID, explode(',', $item['parentID']))];
                                        if (!is_numeric($results[count($results) - 1]["order"])) {
                                            $results[count($results) - 1]["order"] = "0";
                                        }
                                    } else {
                                        $results[count($results) - 1]["order"] = $item['parentID_ord'];
                                    }
                                } else {
                                    $results[count($results) - 1]["order"] = "0";
                                }
                            } else {
                                if (isset($item['ITEM_ORDER']) && $item['ITEM_ORDER'] != '') {
                                    $results[count($results) - 1]["order"] = $item['ITEM_ORDER'];
                                } else {
                                    $results[count($results) - 1]["order"] = "0";
                                }
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
            RSreturnArrayResults($results);
            exit;
        }
    } else {
        if (!empty($allowedItemTypes)) {
            $destinationItemTypes = $allowedItemTypes;
        } else {
            //not allowed itemtypes, get all
            $theQuery = "SELECT `RS_ITEMTYPE_ID` as 'ID' FROM `rs_item_types` WHERE `RS_CLIENT_ID`='" . $clientID . "' ORDER BY `RS_ORDER`";

            // Query the database
            $res = RSquery($theQuery);

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $destinationItemTypes[] = $row['ID'];
                }
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
        getTreePath($clientID, $treePath, array(array('itemTypeID' => $parentItemTypeID, 'mainPropertyID' => $parentItemTypeMainPropertyID, 'mainPropertyType' => $parentItemTypeMainPropertyType)), $destinationItemTypeID, $allowedItemTypes, 10);

        //get all properties needed
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

        //apply filter to itemtype
        $filteredItems = filterItems($clientID, $destinationItemTypeID, $filterID, $fastFilter, $returnOrder);

        //get path for item
        foreach ($filteredItems as $filteredItem) {
            $additionalProps = '';
            foreach ($filteredItem as $property => $value) {
                if ($property != "ID" && $property != "MAINPROP" && $property != "ITEM_ORDER") {
                    $additionalProps .= base64_encode($property) . "," . base64_encode($value) . ";";
                }
            }

            $additionalProps = rtrim($additionalProps, ";");
            $tempPaths = getPathsForItem($clientID, $destinationItemTypeID, $filteredItem['ID'], $treePath, $parentID, $additionalProps, $pathProperties, $mainProperties, $returnOrder, $pathOrders);
            $results = combineItemPaths($results, $tempPaths);
        }
    }
}

array_unshift($results, array("result" => "OK", "filteredID" => implode(",", $destinationItemTypes)));

// And write XML Response back to the application
RSreturnArrayQueryResults($results);
