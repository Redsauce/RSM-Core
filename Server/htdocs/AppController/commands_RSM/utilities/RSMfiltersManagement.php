<?php
//
function addFilter($clientID, $itemTypeID, $filterName, $operatorValue) {
    // get id for new item
    $newID = getNextIdentification("rs_item_type_filters", "RS_FILTER_ID", $clientID);

    // build query to save filter
    $theQuery = 'INSERT INTO `rs_item_type_filters` (`RS_FILTER_ID`, `RS_CLIENT_ID`, `RS_ITEMTYPE_ID`, `RS_NAME`, `RS_OPERATOR`) VALUES (' . $newID . ', ' . $clientID . ', ' . $itemTypeID . ', "' . $filterName . '", "' . $operatorValue . '")';

    // execute query
    return RSquery($theQuery) ? $newID : 0;
}

//
function updateFilterName($clientID, $filterID, $filterName) {
    // build query to save filter
    $theQuery = 'UPDATE `rs_item_type_filters` SET `RS_NAME`="' . $filterName . '" WHERE `RS_CLIENT_ID`=' . $clientID . ' AND `RS_FILTER_ID`=' . $filterID;

    // execute query
    if (RSquery($theQuery)) return 1;

    return 0;
}

//
function updateFilterOperator($clientID, $filterID, $operatorValue) {
    // build query to save filter
    $theQuery = 'UPDATE `rs_item_type_filters` SET `RS_OPERATOR`="' . $operatorValue . '" WHERE `RS_CLIENT_ID`=' . $clientID . ' AND `RS_FILTER_ID`=' . $filterID;

    // execute query
    return RSquery($theQuery) ? 1 : 0;
}

//
function deleteFilter($clientID, $filterID) {
    global $mysqli;

    //begin transaction
    $mysqli->begin_transaction();

    //delete clauses
    if (deleteClauses($clientID, $filterID) == 0) {
        //error deleting
        $mysqli->rollback();
        return 0;
    }

    //delete filter properties
    if (deleteFilterProperties($clientID, $filterID) == 0) {
        //error deleting
        $mysqli->rollback();
        return 0;
    }

    // build query to delete filter
    $theQuery = 'DELETE FROM `rs_item_type_filters` WHERE `RS_CLIENT_ID`=' . $clientID . ' AND `RS_FILTER_ID`=' . $filterID;

    // execute query
    if (RSquery($theQuery)) {
        $mysqli->commit();
        return 1;

    } else {
        //error deleting
        $mysqli->rollback();
        return 0;
    }
}

//
function addClause($clientID, $filterID, $propertyID, $operatorValue, $propertyValue) {

    //first check property
    // build query to get property itemtype
    $theQuery = 'SELECT `rs_categories`.`RS_ITEMTYPE_ID` FROM `rs_item_properties` INNER JOIN (`rs_categories` INNER JOIN `rs_item_type_filters` ON `rs_item_type_filters`.`RS_ITEMTYPE_ID`=`rs_categories`.`RS_ITEMTYPE_ID` AND `rs_item_type_filters`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` ) ON `rs_item_properties`.`RS_CATEGORY_ID`=`rs_categories`.`RS_CATEGORY_ID` AND `rs_item_properties`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` WHERE `rs_item_properties`.`RS_CLIENT_ID`=' . $clientID . ' AND `rs_item_properties`.`RS_PROPERTY_ID`=' . $propertyID . ' AND `rs_item_type_filters`.`RS_FILTER_ID`=' . $filterID;

    // execute query
    $result = RSquery($theQuery);

    if ($result->num_rows > 0) {

        // get id for new item
        $newID = getNextIdentification("rs_item_type_filter_clauses", "RS_CLAUSE_ID", $clientID);

        // build query to save filter
        $theQuery = 'INSERT INTO `rs_item_type_filter_clauses` (`RS_CLAUSE_ID`, `RS_FILTER_ID`, `RS_CLIENT_ID`, `RS_PROPERTY_ID`, `RS_OPERATOR`, `RS_VALUE`) VALUES (' . $newID . ', ' . $filterID . ', ' . $clientID . ', ' . $propertyID . ', "' . $operatorValue . '", "' . $propertyValue . '")';

        // execute query
        if (RSquery($theQuery)) {
            return $newID;

        } else {
            return 0;
        }

    } else {
        return 0;
    }
}

//
function addFilterProperty($clientID, $filterID, $propertyID) {

    //first check property
    // build query to get property itemtype
    $theQuery = 'SELECT `rs_categories`.`RS_ITEMTYPE_ID` FROM `rs_item_properties` INNER JOIN (`rs_categories` INNER JOIN `rs_item_type_filters` ON `rs_item_type_filters`.`RS_ITEMTYPE_ID`=`rs_categories`.`RS_ITEMTYPE_ID` AND `rs_item_type_filters`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` ) ON `rs_item_properties`.`RS_CATEGORY_ID`=`rs_categories`.`RS_CATEGORY_ID` AND `rs_item_properties`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` WHERE `rs_item_properties`.`RS_CLIENT_ID`=' . $clientID . ' AND `rs_item_properties`.`RS_PROPERTY_ID`=' . $propertyID . ' AND `rs_item_type_filters`.`RS_FILTER_ID`=' . $filterID;

    // execute query
    $result = RSquery($theQuery);

    if ($result->num_rows > 0) {

        // build query to save property
        $theQuery = 'INSERT INTO `rs_item_type_filter_properties` (`RS_FILTER_ID`, `RS_CLIENT_ID`, `RS_PROPERTY_ID`) VALUES (' . $filterID . ', ' . $clientID . ', ' . $propertyID . ')';

        // execute query
        return RSquery($theQuery) ? 1 : 0;

    } else
        return 0;
}

/*/
 function updateClause($clientID,$filterID,$clauseID,$propertyID,$operatorValue,$propertyValue){

 //first check property
 // build query to get property itemtype
 $theQuery = 'SELECT `rs_categories`.`RS_ITEMTYPE_ID` FROM `rs_item_properties` INNER JOIN (`rs_categories` INNER JOIN `rs_item_type_filters` ON `rs_item_type_filters`.`RS_ITEMTYPE_ID`=`rs_categories`.`RS_ITEMTYPE_ID` AND `rs_item_type_filters`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` ) ON `rs_item_properties`.`RS_CATEGORY_ID`=`rs_categories`.`RS_CATEGORY_ID` AND `rs_item_properties`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` WHERE `rs_item_properties`.`RS_CLIENT_ID`='.$clientID.' AND `rs_item_properties`.`RS_PROPERTY_ID`='.$propertyID.' AND `rs_item_type_filters`.`RS_FILTER_ID`='.$filterID;

 // execute query
 $result=RSquery($theQuery);

 if($result->num_rows>0){

 // build query to save filter
 $theQuery = 'UPDATE `rs_item_type_filter_clauses` SET `RS_PROPERTY_ID`='.$propertyID.', `RS_OPERATOR`="'.$operatorValue.'", `RS_VALUE`="'.$propertyValue.'" WHERE `RS_CLAUSE_ID`='.$clauseID.' AND `RS_CLIENT_ID`='.$clientID;

 // execute query
 if(RSquery($theQuery)){
 $results['result']="OK";

 }else{
 $results['result']="NOK";
 $results['description']="ERROR EDITING CLAUSE";
 }

 }else{
 $results['result']="NOK";
 $results['description']="ERROR PROPERTY AND ITEMTYPE DO NOT MATCH";
 }
 return $results;
 }*/

//
function deleteClauses($clientID, $filterID) {

    // build query to delete clauses
    $theQuery = 'DELETE FROM `rs_item_type_filter_clauses` WHERE `RS_FILTER_ID`=' . $filterID . ' AND `RS_CLIENT_ID`=' . $clientID;

    // execute query
    if (RSquery($theQuery)) {
        return 1;
    } else {
        return 0;
    }
}

//
function deleteFilterProperties($clientID, $filterID) {

    // build query to delete properties
    $theQuery = 'DELETE FROM `rs_item_type_filter_properties` WHERE `RS_FILTER_ID`=' . $filterID . ' AND `RS_CLIENT_ID`=' . $clientID;

    // execute query
    if (RSquery($theQuery)) {
        return 1;
    } else {
        return 0;
    }
}

//
function getFilters($clientID, $itemTypeID) {
    // build query to get filters
    return RSquery("SELECT `RS_FILTER_ID` AS filterID,
                            RS_NAME AS filterName, RS_OPERATOR AS filterOperator
                    FROM rs_item_type_filters
                    WHERE RS_CLIENT_ID=" . $clientID . "
                        AND RS_ITEMTYPE_ID = " . $itemTypeID . ";");
}

//
function getFilterItemType($clientID, $filterID) {
    //security validation
    if ($filterID == "") {
        $filterID = "0";
    }

    // build query to get filters
    $theQuery = 'SELECT `RS_ITEMTYPE_ID` AS "filterItemType"
                 FROM `rs_item_type_filters`
                 WHERE `RS_CLIENT_ID`="' . $clientID . '"
                    AND `RS_FILTER_ID`="' . $filterID . '"';

    // execute query
    $result = RSquery($theQuery);

    if ($row = $result->fetch_assoc()) {
        $res = $row["filterItemType"];
    } else {
        $res = "-1";
    }

    return $res;
}

//
function getFilterClauses($clientID, $filterID) {
    if ($filterID <= 0)
        return array();

    // build query to get clauses
    $theQuery = 'SELECT `RS_PROPERTY_ID` AS "conditionPropertyID", `RS_OPERATOR` AS "conditionOperator", `RS_VALUE` AS "conditionValue" FROM `rs_item_type_filter_clauses` WHERE `RS_FILTER_ID`="' . $filterID . '" AND `RS_CLIENT_ID`="' . $clientID . '"';

    // execute query
    $result = RSquery($theQuery);

    $results = array();

    while ($row = $result->fetch_assoc())
        $results[] = $row;

    return $results;
}

//
function getFilterProperties($clientID, $filterID) {
    //security validation
    if ($filterID <= 0)
        return array();

    // build query to get clauses
    $theQuery = 'SELECT `RS_PROPERTY_ID` AS "conditionPropertyID" FROM `rs_item_type_filter_properties` WHERE `RS_FILTER_ID`="' . $filterID . '" AND `RS_CLIENT_ID`="' . $clientID . '"';

    // execute query
    $result = RSquery($theQuery);

    $results = array();

    while ($row = $result->fetch_assoc())
        $results[] = $row;

    return $results;
}

//array search for ID field in 2 dimension array
function array_search_ID($needle, $haystack, $field = "ID") {
    foreach ($haystack as $key => $element)
        if ($element[$field] == $needle)
            return ($key);
    return (false);
}

//get group level
function getLevel($a) {
    global $groupResults;
    $i = 0;
    while ($a != 0) {
        $a = $groupResults[array_search_ID($a, $groupResults)]['parent'];
        $i++;
    }
    return $i;
}

//compare 2 groups level
function compareLevel($groupA, $groupB) {
    $a = getLevel($groupA);
    $b = getLevel($groupB);
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

//search tree path recursive
function getTreePath($clientID, &$treePath, $currentBranch, $destinationItemTypeID, $allowedItemTypes, $maxDepth = 10) {

    $lastIndex = count($currentBranch) - 1;

    if ($lastIndex < $maxDepth) {

        $descendants = getDescendantsLevel($clientID, $currentBranch[$lastIndex]['itemTypeID'], $allowedItemTypes);

        $i = array_search_ID($currentBranch[$lastIndex]['itemTypeID'], $descendants, "itemTypeID");

        //special case for base parent=destination itemtype (not recursive root itemtype listing)
        if ($lastIndex == 0 && $i === false && $currentBranch[$lastIndex]['itemTypeID'] == $destinationItemTypeID) {
            $currentBranch[$lastIndex]['recursive'] = 0;
            $treePath[] = $currentBranch;
        } else {
            if ($i !== false) {
                $currentBranch[$lastIndex]['recursive'] = 1;
                $currentBranch[$lastIndex]['recursivePropertyID'] = $descendants[$i]['propertyID'];
            } else {
                $currentBranch[$lastIndex]['recursive'] = 0;
            }

            foreach ($descendants as $descendant) {
                //temporary branch
                $tempBranch = $currentBranch;

                if ($descendant['itemTypeID'] == $destinationItemTypeID) {
                    //destination found, save path and end branch
                    if ($descendant['itemTypeID'] != $currentBranch[$lastIndex]['itemTypeID']) {
                        //add last descendant only if not already in list (recursive itemtype)
                        //check destination is recursive
                        $descendants = getDescendantsLevel($clientID, $descendant['itemTypeID'], $allowedItemTypes);

                        $i = array_search_ID($descendant['itemTypeID'], $descendants, "itemTypeID");

                        if ($i !== false) {
                            $descendant['recursive'] = 1;
                            $descendant['recursivePropertyID'] = $descendants[$i]['propertyID'];
                        } else $descendant['recursive'] = 0;

                        $tempBranch[] = $descendant;
                    }
                    $treePath[] = $tempBranch;

                } elseif (array_search_ID($descendant['itemTypeID'], $currentBranch, "itemTypeID") === false) {
                    //continue recursive searching
                    $tempBranch[] = $descendant;
                    getTreePath($clientID, $treePath, $tempBranch, $destinationItemTypeID, $allowedItemTypes, $maxDepth);

                } else {
                    //cyclic branch, discard (do nothing)
                }
            }
        }
    }
}

// get a level of descendants
function getDescendantsLevel($clientID, $parentItemTypeID, $allowedItemTypes) {
    $results = array();

    // get depending itemtypes
    $theQuery = 'SELECT `rs_item_properties`.`RS_NAME` AS "propertyName", `rs_categories`.`RS_ITEMTYPE_ID` AS "itemTypeID", `rs_item_properties`.`RS_TYPE` AS "propertyType", `rs_item_properties`.`RS_PROPERTY_ID` AS "propertyID" FROM `rs_item_properties` INNER JOIN `rs_categories` ON `rs_item_properties`.`RS_CATEGORY_ID`=`rs_categories`.`RS_CATEGORY_ID` AND `rs_item_properties`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` WHERE `rs_item_properties`.`RS_CLIENT_ID`=' . $clientID . ' AND `rs_item_properties`.`RS_REFERRED_ITEMTYPE`=' . $parentItemTypeID;

    // execute query
    $result = RSquery($theQuery);

    if($result) {
        while ($row = $result->fetch_assoc()){
            if (count($allowedItemTypes) == 0 || in_array($row['itemTypeID'], $allowedItemTypes)){
                $row['mainPropertyID']=getMainPropertyID($row['itemTypeID'], $clientID);
                $row['mainPropertyType']=getPropertyType($row['mainPropertyID'], $clientID);
                $results[] = $row;
            }
        }
    }

    //get app related itemtypes
    $appItemTypeID = getAppItemTypeID_RelatedWith($parentItemTypeID, $clientID);

    if($appItemTypeID > 0) {
        // Look for app related descendad item types
        $theQuery = 'SELECT `rs_item_properties`.`RS_NAME` AS "propertyName", `rs_item_type_app_relations`.`RS_ITEMTYPE_ID` AS "itemTypeID", `rs_property_app_definitions`.`RS_TYPE` AS "propertyType", `rs_property_app_relations`.`RS_PROPERTY_ID` AS "propertyID" FROM `rs_item_properties` INNER JOIN (`rs_item_type_app_relations` INNER JOIN (`rs_property_app_relations` INNER JOIN `rs_property_app_definitions` ON `rs_property_app_relations`.`RS_PROPERTY_APP_ID`=`rs_property_app_definitions`.`RS_ID`) ON `rs_property_app_definitions`.`RS_ITEM_TYPE_ID`=`rs_item_type_app_relations`.`RS_ITEMTYPE_APP_ID` AND `rs_property_app_relations`.`RS_CLIENT_ID`=`rs_item_type_app_relations`.`RS_CLIENT_ID` ) ON `rs_item_properties`.`RS_PROPERTY_ID` = `rs_property_app_relations`.`RS_PROPERTY_ID` AND `rs_property_app_relations`.`RS_CLIENT_ID`=`rs_item_properties`.`RS_CLIENT_ID` WHERE `rs_property_app_relations`.`RS_CLIENT_ID`=' . $clientID . ' AND `rs_property_app_definitions`.`RS_REFERRED_ITEMTYPE` = ' . $appItemTypeID;

        // execute query
        $result = RSquery($theQuery);
        while ($row = $result->fetch_assoc()){
            if (count($allowedItemTypes) == 0 || in_array($row['itemTypeID'], $allowedItemTypes)){
                $row['mainPropertyID']=getMainPropertyID($row['itemTypeID'], $clientID);
                $row['mainPropertyType']=getPropertyType($row['mainPropertyID'], $clientID);
                $results[] = $row;
            }
        }
    }

    return $results;
}

//
function filterItems($clientID, $itemTypeID, $filterID, $fastFilter = '', $returnOrder = 0, $mainPropName = "MAINPROP") {
    global $RSuserID;

    $returnArray = array();
    $filterProperties = array();
    $returnProperties = array();
    $properties = array();

    // We always return the main property
    $mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
    $returnProperties[] = array('ID' => $mainPropertyID, 'name' => $mainPropName);

    $operator = '';
    if ($filterID > 0) {
        // If a filter is defined, add the properties to filter to the $filterProperties array
        $clauses = getFilterClauses($clientID, $filterID);
        $properties = getFilterProperties($clientID, $filterID);

        // build query to get filters
        $theQuery = 'SELECT `RS_OPERATOR` FROM `rs_item_type_filters` WHERE `RS_CLIENT_ID`="' . $clientID . '" AND `RS_FILTER_ID`="' . $filterID . '"';

        // execute query
        $result = RSquery($theQuery);

        if ($result->num_rows == 1) {
            $res = $result->fetch_assoc();
            $operator = $res["RS_OPERATOR"];
        } else
            $operator = "AND";

        // additional return properties from filter
        foreach ($properties as $property)
            $returnProperties[] = array('ID' => $property["conditionPropertyID"], 'name' => getClientPropertyName($property["conditionPropertyID"], $clientID));

        // build filter array
        foreach ($clauses as $clause)
            $filterProperties[] = array('ID' => $clause["conditionPropertyID"], 'value' => $clause["conditionValue"], 'mode' => $clause["conditionOperator"]);
    }

    //check fast filter and generate ids list
    $ids = array();
    if ($fastFilter != '') {
        // get all available properties for fast filtering
        $visibleProperties = getVisibleProperties_extended($itemTypeID, $clientID, $RSuserID, false, true, false, false, true);

        $fastFilterArr = preg_split("/\s+/",$fastFilter);
        foreach ($fastFilterArr as $fastFilterVal) {
            $idsForFilter[$fastFilterVal] = array(-1);
        }

        foreach ($visibleProperties as $property) {
            // get property
            $propertyRows = getItemsPropertyValues($property['ID'], $clientID, '', $property['type'], $itemTypeID, true);

            foreach ($propertyRows as $propertyItemID => $propertyRowValue) {
                foreach ($fastFilterArr as $fastFilterVal) {
                    //check filter not empty. This can be caused by encoding mismatch in html_entity_decode
                    //TO DO: check encoding is always UTF-8 for $fastFilter
                    if (normaliza(html_entity_decode($fastFilterVal, ENT_COMPAT, 'UTF-8')) != '') {
                        if (mb_stripos(normaliza(html_entity_decode($propertyRowValue, ENT_COMPAT, 'UTF-8')), normaliza(html_entity_decode($fastFilterVal, ENT_COMPAT, 'UTF-8'))) !== false && array_search($propertyItemID, $idsForFilter[$fastFilterVal])=== false){
                            $idsForFilter[$fastFilterVal][]=$propertyItemID;
                        }
                    }
                }
            }
        }

        //intersect results (find all words from fastFilter)
        if(count($idsForFilter)>1){
            $ids = call_user_func_array('array_intersect',$idsForFilter);
        }else{
            $ids = reset($idsForFilter);
        }
    }
    // get items
    $results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true, '', implode(",", $ids), $operator, $returnOrder);

    return $results;
}

//
function loopRecursiveItemType($clientID, $treePath, $temporaryItemsInPath, $targetParentID, $pathProperties = array(), $mainProperties = array(), $returnOrder = 0, $pathOrders = array()) {

    $temporaryBranches = array();

    $recursiveParentsList = (isset($pathProperties[$treePath[count($treePath) - 1]["recursivePropertyID"]]) && array_key_exists($temporaryItemsInPath[0]["nodeID"],$pathProperties[$treePath[count($treePath) - 1]["recursivePropertyID"]]))?$pathProperties[$treePath[count($treePath) - 1]["recursivePropertyID"]][$temporaryItemsInPath[0]["nodeID"]]:getItemPropertyValue($temporaryItemsInPath[0]["nodeID"], $treePath[count($treePath) - 1]["recursivePropertyID"], $clientID);

    //check for multiidentifier
    $recursiveParentIDs = explode(",", $recursiveParentsList);

    if ($returnOrder) {
        $recursiveOrdersList = (isset($pathOrders[$treePath[count($treePath) - 1]["recursivePropertyID"]]) && array_key_exists($temporaryItemsInPath[0]["nodeID"],$pathOrders[$treePath[count($treePath) - 1]["recursivePropertyID"]]))?$pathOrders[$treePath[count($treePath) - 1]["recursivePropertyID"]][$temporaryItemsInPath[0]["nodeID"]]:"0";

        $recursiveOrders = explode(",", $recursiveOrdersList);
    }

    for ($i = 0; $i < count($recursiveParentIDs); $i++) {
        $recursiveParentID = $recursiveParentIDs[$i];
        $auxItemsInPath = $temporaryItemsInPath;
        if ((count($treePath) > 1 || $recursiveParentID != $targetParentID) && $recursiveParentID > 0) {
            $auxItemsInPath[0]["parentID"] = $recursiveParentID;
            $auxItemsInPath[0]["parentItemType"] = $treePath[count($treePath) - 1]["itemTypeID"];
            $auxItemsInPath[0]["parentPropertyID"] = $treePath[count($treePath) - 1]["recursivePropertyID"];
            if ($returnOrder) {
                $auxItemsInPath[0]["order"] = (isset($recursiveOrders[$i])&&$recursiveOrders[$i]!='')?$recursiveOrders[$i]:"0";
            }
            //
            $itemName = (isset($mainProperties[$treePath[count($treePath) - 1]["itemTypeID"]]) && array_key_exists($recursiveParentID,$mainProperties[$treePath[count($treePath) - 1]["itemTypeID"]]))?$mainProperties[$treePath[count($treePath) - 1]["itemTypeID"]][$recursiveParentID]:getMainPropertyValue($treePath[count($treePath) - 1]["itemTypeID"], $recursiveParentID, $clientID);
            array_unshift($auxItemsInPath, array("nodeID" => $recursiveParentID, "nodeItemType" => $treePath[count($treePath) - 1]["itemTypeID"], "name" => $itemName, "parentID" => '', "parentItemType" => '', "parentPropertyID" => '', "childs" => $auxItemsInPath[0]["nodeID"] . ',' . $auxItemsInPath[0]["nodeItemType"]));
            if ($returnOrder) {
                $auxItemsInPath[0]["order"] = "0";
            }
            //continue with recursive itemtype
            $temporaryBranches = array_merge($temporaryBranches, loopRecursiveItemType($clientID, $treePath, $auxItemsInPath, $targetParentID, $pathProperties, $mainProperties, $returnOrder, $pathOrders));

        } elseif (count($treePath) == 1) {
            if ($recursiveParentID == $targetParentID) {
                $auxItemsInPath[0]["parentID"] = $recursiveParentID;
                $auxItemsInPath[0]["parentItemType"] = $treePath[0]["itemTypeID"];
                $auxItemsInPath[0]["parentPropertyID"] = $treePath[0]["recursivePropertyID"];
                if ($returnOrder) {
                    $auxItemsInPath[0]["order"] = (isset($recursiveOrders[$i])&&$recursiveOrders[$i]!='')?$recursiveOrders[$i]:"0";
                }

                //branch finished, return
                $temporaryBranches[] = $auxItemsInPath;
            }
        } elseif ($recursiveParentID == "" || $recursiveParentID == "0") {
            //recursive finished, continue with normal path processing
            $treePath[count($treePath) - 1]["recursive"] = 0;

            $temporaryBranches = array_merge($temporaryBranches, processBranches($clientID, $treePath, $auxItemsInPath, $targetParentID, $pathProperties, $mainProperties, $returnOrder, $pathOrders));
        }
    }

    return $temporaryBranches;
}

//
function processBranches($clientID, $treePath, $temporaryItemsInPath, $targetParentID, $pathProperties = array(), $mainProperties = array(), $returnOrder = 0, $pathOrders = array()) {

    $temporaryBranches = array();

    if (count($treePath) > 0) {
        //check for recursive parent if exists
        if ($treePath[count($treePath) - 1]["recursive"] == 1) {
            $temporaryBranches = loopRecursiveItemType($clientID, $treePath, $temporaryItemsInPath, $targetParentID, $pathProperties, $mainProperties, $returnOrder, $pathOrders);
        } else {
            if (count($treePath) > 1) {
                $parentsList = (isset($pathProperties[$treePath[count($treePath) - 1]["propertyID"]]) && array_key_exists($temporaryItemsInPath[0]["nodeID"],$pathProperties[$treePath[count($treePath) - 1]["propertyID"]]))?$pathProperties[$treePath[count($treePath) - 1]["propertyID"]][$temporaryItemsInPath[0]["nodeID"]]:getItemPropertyValue($temporaryItemsInPath[0]["nodeID"], $treePath[count($treePath) - 1]["propertyID"], $clientID, $treePath[count($treePath) - 1]["propertyType"], $treePath[count($treePath) - 1]["itemTypeID"]);

                //check for multiidentifier
                $parentIDs = explode(",", $parentsList);

                if ($returnOrder) {
                    $ordersList = (isset($pathOrders[$treePath[count($treePath) - 1]["propertyID"]]) && array_key_exists($temporaryItemsInPath[0]["nodeID"],$pathOrders[$treePath[count($treePath) - 1]["propertyID"]]))?$pathOrders[$treePath[count($treePath) - 1]["propertyID"]][$temporaryItemsInPath[0]["nodeID"]]:"0";

                    $orders = explode(",", $ordersList);
                }

                for ($i = 0; $i < count($parentIDs); $i++) {
                    $parentID = $parentIDs[$i];
                    $auxItemsInPath = $temporaryItemsInPath;
                    $auxTreePath = $treePath;
                    if ($parentID > 0) {
                        $auxItemsInPath[0]["parentID"] = $parentID;
                        $auxItemsInPath[0]["parentItemType"] = $treePath[count($treePath) - 2]["itemTypeID"];
                        $auxItemsInPath[0]["parentPropertyID"] = $treePath[count($treePath) - 1]["propertyID"];
                        if ($returnOrder) {
                            $auxItemsInPath[0]["order"] = (isset($orders[$i])&&$orders[$i]!='')?$orders[$i]:"0";
                        }
                        if (count($treePath) > 2 || $parentID != $targetParentID) {
                            //$itemName = getMainPropertyValue($treePath[count($treePath) - 2]["itemTypeID"], $parentID, $clientID);
                            $itemName = (isset($mainProperties[$treePath[count($treePath) - 2]["itemTypeID"]]) && array_key_exists($parentID,$mainProperties[$treePath[count($treePath) - 2]["itemTypeID"]]))?$mainProperties[$treePath[count($treePath) - 2]["itemTypeID"]][$parentID]:getItemPropertyValue($parentID, $treePath[count($treePath) - 2]["mainPropertyID"], $clientID, $treePath[count($treePath) - 2]["mainPropertyType"], $treePath[count($treePath) - 2]["itemTypeID"]);
                            array_unshift($auxItemsInPath, array("nodeID" => $parentID, "nodeItemType" => $treePath[count($treePath) - 2]["itemTypeID"], "name" => $itemName, "parentID" => '', "parentItemType" => '', "parentPropertyID" => '', "childs" => $auxItemsInPath[0]["nodeID"] . ',' . $auxItemsInPath[0]["nodeItemType"]));
                            if ($returnOrder) {
                                $auxItemsInPath[0]["order"] = "0";
                            }
                            //remove last tree element
                            $auxTreePath = $treePath;
                            array_pop($auxTreePath);

                            //continue with recursive branches
                            $temporaryBranches = array_merge($temporaryBranches, processBranches($clientID, $auxTreePath, $auxItemsInPath, $targetParentID, $pathProperties, $mainProperties, $returnOrder, $pathOrders));
                        } else {
                            //branch finished, return
                            $temporaryBranches[] = $auxItemsInPath;
                        }
                    } elseif (count($treePath) == 2 && $parentID == $targetParentID) {
                        //special case when base itemtype=0 and is child itemtype
                        $auxItemsInPath[0]["parentID"] = 0;
                        $auxItemsInPath[0]["parentItemType"] = $treePath[0]["itemTypeID"];
                        if ($returnOrder) {
                            $auxItemsInPath[0]["order"] = (isset($orders[$i])&&$orders[$i]!='')?$orders[$i]:"0";
                        }
                        //branch finished, return
                        $temporaryBranches[] = $auxItemsInPath;
                    }
                }
            } elseif ($targetParentID == 0) {
                //base itemtype in root
                $temporaryItemsInPath[0]["parentID"] = 0;
                $temporaryItemsInPath[0]["parentItemType"] = $treePath[0]["itemTypeID"];
                if ($returnOrder) {
                    $temporaryItemsInPath[0]["order"] = getItemOrder($clientID, $temporaryItemsInPath[0]["nodeItemType"], $temporaryItemsInPath[0]["nodeID"]);
                }
                //branch finished, return
                $temporaryBranches[] = $temporaryItemsInPath;
            }
        }
    }

    return $temporaryBranches;
}

//
function getPathsForItem($clientID, $itemTypeID, $itemID, $treePaths, $targetParentID, $additionalProps, $pathProperties = array(), $mainProperties = array(), $returnOrder = 0, $pathOrders = array()) {

    $itemsInPath = array();

    foreach ($treePaths as $treePath) {
        //start with item
        $temporaryItemsInPath = array();
        $temporaryBranches = array();

        $itemName = (isset($mainProperties[$itemTypeID]) && array_key_exists($itemID,$mainProperties[$itemTypeID]))?$mainProperties[$itemTypeID][$itemID]:getMainPropertyValue($itemTypeID, $itemID, $clientID);

        $temporaryItemsInPath[] = array("nodeID" => $itemID, "nodeItemType" => $itemTypeID, "name" => $itemName, "parentID" => '', "parentItemType" => '', "parentPropertyID" => '', "childs" => '');
        if ($returnOrder) {
            $temporaryItemsInPath[0]["order"] = "0";
        }
        if ($additionalProps != "") {
            $temporaryItemsInPath[0]["extraColumns"] = $additionalProps;
        }

        //process treePath
        $temporaryBranches = processBranches($clientID, $treePath, $temporaryItemsInPath, $targetParentID, $pathProperties, $mainProperties, $returnOrder, $pathOrders);

        foreach ($temporaryBranches as $temporaryItemsInPath) {
            //last treePathItem
            if ($temporaryItemsInPath[0]["parentID"] != $targetParentID) {
                if ($targetParentID == 0) {
                    $temporaryItemsInPath[0]["parentID"] = 0;
                    $temporaryItemsInPath[0]["parentItemType"] = $treePath[0]["itemTypeID"];
                    if ($returnOrder) {
                        $temporaryItemsInPath[0]["order"] = getItemOrder($clientID, $temporaryItemsInPath[0]["nodeItemType"], $temporaryItemsInPath[0]["nodeID"]);
                    }

                    //completed branch, add to completed paths
                    $itemsInPath = array_merge($itemsInPath, $temporaryItemsInPath);
                }
            } else {
                //completed branch, add to completed paths
                $itemsInPath = array_merge($itemsInPath, $temporaryItemsInPath);
            }
        }
    }

    return $itemsInPath;
}

//
function combineItemPaths($pathsArray, $itemsToAdd) {

    foreach ($itemsToAdd as $itemToAdd) {
        $found = false;
        foreach ($pathsArray as $key => $path) {
            // TO DO: check if property should match too
            if ($itemToAdd['nodeID'] == $path['nodeID'] && $itemToAdd['nodeItemType'] == $path['nodeItemType'] && $itemToAdd['parentID'] == $path['parentID'] && $itemToAdd['parentItemType'] == $path['parentItemType']) {
                $found = $key;
                break;
            }
        }
        if ($found === false) {
            $pathsArray[] = $itemToAdd;
        } else {
            //combine childs
            $pathsArray[$found]['childs'] = implode(";", array_unique(array_merge(explode(";", $pathsArray[$found]['childs']), explode(";", $itemToAdd['childs']))));
        }
    }

    return $pathsArray;
}

//
function applyExternalFilters($itemTypeID, $clientID, $results, $extFilterRules) {
    $extFilterArr = explode(',', $extFilterRules);

    $pathProperties = array();
    $mainProperties = array();

    foreach ($extFilterArr as $extFilter) {
        // get property data
        $filterArr = explode(';', $extFilter);

        // get all ascendants matching the filter
        $ascendantItemTypeID = getItemTypeIDFromProperties(array($filterArr[0]), $clientID);
        $filterProperties = array( array('ID' => parsePID($filterArr[0],$clientID), 'value' => str_replace("&amp;", "&", htmlentities(($filterArr[1]), ENT_COMPAT, "UTF-8")), 'mode' => $filterArr[2]));

        $validAscendants = getFilteredItemsIDs($ascendantItemTypeID, $clientID, $filterProperties, array());

        //get ascendant ItemType MainProperty ID and Type for treePath root level
        $ascendantItemTypeMainPropertyID   = getMainPropertyID($ascendantItemTypeID, $clientID);
        $ascendantItemTypeMainPropertyType = getPropertyType  ($ascendantItemTypeMainPropertyID, $clientID);

        // get all paths between filtered and destination itemtype
        $allowedItemTypes = array();
        if (isset($filterArr[3]) && $filterArr[3] != "") $allowedItemTypes = explode(",", ($filterArr[3]));

        $treePath = array();

        getTreePath($clientID, $treePath, array( array('itemTypeID' => $ascendantItemTypeID,'mainPropertyID'=>$ascendantItemTypeMainPropertyID,'mainPropertyType'=>$ascendantItemTypeMainPropertyType)), $itemTypeID, $allowedItemTypes, 4);

        //get all properties needed
        foreach ($treePath as $path){
          foreach ($path as $step){
            if(!array_key_exists($step['itemTypeID'],$mainProperties)){
              $mainProperties[$step['itemTypeID']] = getItemsPropertyValues($step['mainPropertyID'], $clientID,'', $step['mainPropertyType'], $step['itemTypeID']);
            }
            if(array_key_exists('propertyID',$step)&&!array_key_exists($step['propertyID'],$pathProperties)){
              $pathProperties[$step['propertyID']] = getItemsPropertyValues($step['propertyID'], $clientID,'', $step['propertyType'], $step['itemTypeID']);
            }
            if(array_key_exists('recursivePropertyID',$step)&&!array_key_exists($step['recursivePropertyID'],$pathProperties)){
              $pathProperties[$step['recursivePropertyID']] = getItemsPropertyValues($step['recursivePropertyID'], $clientID,'', '', $step['itemTypeID']);
            }
          }
        }

        // get ascendants path for each item in results
        $total = count($results);
        $removed = 0;
        for ($i = $total - 1; $i >= 0; $i--) {
            // construct IDs tree for each result
            $tempPaths = getPathsForItem($clientID, $itemTypeID, $results[$i]['ID'], $treePath, 0, "", $pathProperties, $mainProperties);

            // search for any valid (filter matching) ascendant in generated paths
            $found = false;
            foreach ($validAscendants as $validAscendant)
                foreach ($tempPaths as $element)
                    if ($element["nodeID"] == $validAscendant["ID"] && $element["nodeItemType"] == $ascendantItemTypeID) {
                        $found = true;
                        break 2;
                    }

            // Remove item from results list if it doesn't have any matching ascendant
            if (!$found) {
                unset($results[$i]);
                $removed++;
            }
        }

		// We reindex the array because the associative indexes are wrong after the removal of the items due to the external filtering
        if (is_a($results,'SplFixedArray')) {
            $res = new SplFixedArray($total-$removed);
            $i = 0;
            foreach ($results as $key => $result) {
                if (isset($results[$key])) {
                    $res[$i] = $results[$key];
                    $i++;
                }
            }
        } else {
            $res = array_values($results);
        }
    }

    return $res;
}

function getRecursivePropertyID($itemTypeID, $clientID) {
	// prepare query
	$theQuery = 'SELECT RS_PROPERTY_ID AS "ID" FROM rs_item_properties WHERE RS_PROPERTY_ID IN (' . implode(',', getProperties($itemTypeID, $clientID)) . ') AND RS_REFERRED_ITEMTYPE = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID;

    $appItemTypeID = getAppItemTypeID_RelatedWith($itemTypeID, $clientID);
    if($appItemTypeID != '0') {
        $theQuery .= ' UNION SELECT rs_property_app_relations.RS_PROPERTY_ID AS "ID" FROM rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_definitions.RS_ID = rs_property_app_relations.RS_PROPERTY_APP_ID WHERE rs_property_app_relations.RS_PROPERTY_ID IN (' . implode(',', getProperties($itemTypeID, $clientID)) . ') AND rs_property_app_definitions.RS_REFERRED_ITEMTYPE = ' . $appItemTypeID . ' AND rs_property_app_relations.RS_CLIENT_ID = ' . $clientID;
    }

	// execute query
	$result = RSQuery($theQuery);

	if (!$result || $result->num_rows == 0) {
		return '0';
	} // Recursive property not found

	$row = $result->fetch_assoc();
	return $row['ID'];
}


//
function normaliza ($cadena) {
    // Definition of original characters and their corresponding replacements.

    $originales =  'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåèéêëìíîïòóôõöùúûýýÿŔŕ';
    $modificadas = 'AAAAAAEEEEIIIIOOOOOUUUUYaaaaaaeeeeiiiiooooouuuyyyRr';
   
    // Convert the input string from UTF-8 to ISO-8859-1
    $cadena = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $cadena);
    // Replace the original characters with their corresponding replacements
    $cadena = strtr($cadena, $originales, $modificadas);
    // Convert the modified string from ISO-8859-1 back to UTF-8
    $cadena = iconv('ISO-8859-1', 'UTF-8', $cadena);

    return $cadena;
}
