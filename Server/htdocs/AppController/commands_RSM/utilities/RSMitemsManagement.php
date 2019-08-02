<?php
require_once 'RSMpropertiesManagement.php';
require_once 'RSMidentificationFunctions.php';
require_once 'RSMErrors.php';
require_once 'RSMdefinitions.php';
require_once "RSMfiltersManagement.php";
require_once 'RSMtokensManagement.php';
require_once "RSMmediaManagement.php";
require_once "RSMcacheManagement.php";

function getPropertyIDs_usingSysName($appNames, $clientID) {
   // prepare query
   $theQuery = 'SELECT a.RS_NAME AS "appName", c.RS_PROPERTY_ID AS "ID", c.RS_NAME AS "name", c.RS_TYPE AS "type", c.RS_DEFAULTVALUE AS "defaultValue" FROM rs_property_app_definitions a INNER JOIN rs_property_app_relations b ON (a.RS_ID = b.RS_PROPERTY_APP_ID) INNER JOIN rs_item_properties c USING (RS_CLIENT_ID, RS_PROPERTY_ID) WHERE a.RS_NAME IN ("' . implode('","', $appNames) . '") AND b.RS_CLIENT_ID = ' . $clientID . ' AND c.RS_CLIENT_ID = ' . $clientID;

   // execute query
   $result = RSQuery($theQuery);

   if (!$result)
      // The query was not OK
      return array();

      // The query was OK, so let's put the results in the properties array
      $properties = array();
      while ($row = $result->fetch_assoc())
      $properties[] = $row;

      // Finally, order the properties in order to match the order in which the sysNames were specified
      $properties_ordered = array();
      foreach ($appNames as $name)
          foreach ($properties as $property)
              if ($property['appName'] == $name) {
                  // We found a match for the properties, so add it to the response array
                  $properties_ordered[] = $property;
                  // As only a property will be returned for every appName, we can stop this iteration and continue with the main loop
                  break;
              }

      // Finally return back the properties, already ordered
      return $properties_ordered;
}

function getPropertiesExtendedForItemAndUser($itemTypeID, $itemID, $clientID, $userID) {
    // build a fast query to get user properties
    $theQuery_getProperties = 'SELECT DISTINCT
                rs_categories.RS_NAME AS "categoryName",
                rs_categories.RS_ORDER,
                rs_item_properties.RS_PROPERTY_ID AS "propertyID",
                rs_item_properties.RS_NAME AS "propertyName",
                rs_item_properties.RS_TYPE AS "propertyType",
                rs_item_properties.RS_AUDIT_TRAIL as "auditTrail",
                rs_item_properties.RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED as "auditTrailDescriptionRequired",
                rs_item_properties.RS_SEARCHABLE as "searchable",
                rs_item_properties.RS_ORDER

        FROM rs_categories
                INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID)
                INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID)
                INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID)

        WHERE
                        rs_categories.RS_ITEMTYPE_ID            = ' . $itemTypeID . '
                AND rs_categories.RS_CLIENT_ID                = ' . $clientID . '
                AND rs_item_properties.RS_CLIENT_ID     = ' . $clientID . '
                AND rs_properties_groups.RS_CLIENT_ID = ' . $clientID . '
                AND rs_users_groups.RS_USER_ID                = ' . $userID . '
                AND rs_users_groups.RS_CLIENT_ID            = ' . $clientID . '

        ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER';

    // execute query
    $theProperties = RSquery($theQuery_getProperties);

    // get properties values
    $properties = array();
    $results = array();

    $categoryName = '';

    if($theProperties) {
        while ($row = $theProperties->fetch_assoc()) {

            // save the property ID
            $properties[] = $row['propertyID'];

            //get property app name if exists
            $propertyAppName = getAppPropertyName_RelatedWith($row['propertyID'], $clientID);

            // get property value
            $propertyValue = getItemPropertyValue($itemID, $row['propertyID'], $clientID, $row['propertyType']);
            $propertyRealValue = '';

            if (isSingleIdentifier($row['propertyType']) || (isIdentifier2itemtype($row['propertyType'])) || (isIdentifier2property($row['propertyType']))) {
                // the property is a single-identifier, so we need to translate the value
                $propertyRealValue = $propertyValue;
                $propertyValue = translateSingleIdentifier($row['propertyID'], $propertyRealValue, $clientID, $row['propertyType']);
            } elseif (isMultiIdentifier($row['propertyType'])) {
                // the property is a multi-identifiers, so we need to translate the value
                $propertyRealValue = $propertyValue;
                $propertyValue = translateMultiIdentifier($row['propertyID'], $propertyRealValue, $clientID);
            }

            if ($row['categoryName'] != $categoryName) {
                // store info
                $results[] = array('category' => $row['categoryName']);

                $categoryName = $row['categoryName'];
            }

            // store info
            $results[] = array('id' => $row['propertyID'], 'name' => $row['propertyName'], 'type' => $row['propertyType'], 'value' => $propertyValue, 'realValue' => $propertyRealValue, 'appName' => $propertyAppName, 'auditTrail' => $row['auditTrail'], 'auditTrailDescriptionRequired' => $row['auditTrailDescriptionRequired'], 'searchable' => $row['searchable']);
        }
    }

    return $results;
}

function getPropertiesExtendedForItemAndToken($itemTypeID, $itemID, $RStoken) {
    $clientID = RSclientFromToken($RStoken);

    // build a fast query to get user properties
    $theQuery_getProperties = 'SELECT DISTINCT rs_categories.RS_NAME AS "cName",
                                         rs_categories.RS_ORDER,
                                         rs_item_properties.RS_PROPERTY_ID AS "pID",
                                         rs_item_properties.RS_NAME AS "pName",
                                         rs_item_properties.RS_TYPE AS "pType",
                                         rs_item_properties.RS_ORDER

        FROM rs_categories
                INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID)
                INNER JOIN rs_token_permissions USING (RS_CLIENT_ID, RS_PROPERTY_ID)
                INNER JOIN rs_tokens USING (RS_CLIENT_ID)

        WHERE rs_categories.RS_ITEMTYPE_ID                 =     ' . $itemTypeID . '
                AND rs_categories.RS_CLIENT_ID                 =     ' . $clientID . '
                AND rs_item_properties.RS_CLIENT_ID        =     ' . $clientID . '
                AND rs_token_permissions.RS_CLIENT_ID    =     ' . $clientID . '
                AND rs_tokens.RS_TOKEN                                 = \'' . $RStoken . '\'

        ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER';

    // execute query
    $theProperties = RSquery($theQuery_getProperties);

    // get properties values
    $properties = array();
    $results = array();

    $categoryName = '';
    if($theProperties) {
        while ($row = $theProperties->fetch_assoc()) {

            // Only return properties that user have READ permission
            if (RShasTokenPermission($RStoken, $row['pID'], "READ")) {

                // save the property ID
                $properties[] = $row['pID'];

                // get property value
                $propertyValue = getItemPropertyValue($itemID, $row['pID'], $clientID, $row['pType']);
                $propertyRealValue = '';

                if (isSingleIdentifier($row['pType']) || (isIdentifier2itemtype($row['pType'])) || (isIdentifier2property($row['pType']))) {
                    // the property is a single-identifier, so we need to translate the value
                    $propertyRealValue = $propertyValue;
                    $propertyValue = translateSingleIdentifier($row['pID'], $propertyRealValue, $clientID, $row['pType']);
                } elseif (isMultiIdentifier($row['pType'])) {
                    // the property is a multi-identifiers, so we need to translate the value
                    $propertyRealValue = $propertyValue;
                    $propertyValue = translateMultiIdentifier($row['pID'], $propertyRealValue, $clientID);
                }

                if ($row['cName'] != $categoryName) {
                    // store info
                    $results[] = array('category' => $row['cName']);

                    $categoryName = $row['cName'];
                }

                // store info
                $results[] = array('id' => $row['pID'], 'name' => $row['pName'], 'type' => $row['pType'], 'value' => $propertyValue, 'realValue' => $propertyRealValue, );
            }
        }
    }

    return $results;
}

function getPropertyIDs($propIDs, $clientID) {
    // prepare query
    //Concatenate property Ids and check not empty
    $propIDsString = implode(',', $propIDs);
    if ($propIDsString == "") $propIDsString = '""';

    $theQuery = 'SELECT
            RS_PROPERTY_ID AS "ID",
            RS_NAME AS "name",
            RS_TYPE AS "type",
            RS_DEFAULTVALUE AS "defaultValue"

    FROM rs_item_properties

    WHERE
         RS_PROPERTY_ID IN (' . $propIDsString . ')
         AND RS_CLIENT_ID = ' . $clientID;

    // execute query
    $result = RSQuery($theQuery);

    if (!$result) {
        return array();
    }// The query was not OK

    // The query was OK, so let's put the results in the properties array
    $properties = array();
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }

    // Finally, order the properties in order to match the order in which the sysNames were specified
    $properties_ordered = array();
    foreach ($propIDs as $propID) {
        foreach ($properties as $property) {
            if ($property['ID'] == $propID) {
                // We found a match for the properties, so add it to the response array
                $properties_ordered[] = $property;
                // As only a property will be returned for every sysName, we can stop this iteration and continue with the main loop
                break;
            }
        }
    }

    // Finally return back the properties, already ordered
    return $properties_ordered;
}

function getProperties_byIDs($propertyIDs, $clientID, $fields) {
    // prepare query
    $theQuery = 'SELECT RS_PROPERTY_ID, ' . $fields . ' FROM rs_item_properties WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_PROPERTY_ID IN (' . $propertyIDs . ')';

    // execute query
    $result = RSQuery($theQuery);

    if ($result) {
        // query OK
        $properties = array();

        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }

        return $properties;
    } else {
        // query NOK
        return false;
    }
}

function getProperties($itemTypeID, $clientID){
        $categories = "
                SELECT RS_CATEGORY_ID
                FROM rs_categories
                WHERE RS_ITEMTYPE_ID = '".$itemTypeID."'
                        AND RS_CLIENT_ID = '".$clientID."'";

        $properties = RSQuery("
                SELECT RS_PROPERTY_ID
                FROM rs_item_properties
                WHERE RS_CLIENT_ID = '".$clientID."'
                        AND RS_CATEGORY_ID IN (".$categories.");");

        $props = array();
    if($properties) {
            while ($row = $properties->fetch_assoc()){
                    $props[] = $row['RS_PROPERTY_ID'];
            }
    }
        return $props;
}

function getImage($clientID, $propertyID, $itemID) {
    $theQuery = "
            SELECT p.RS_DATA, p.RS_NAME, p.RS_SIZE
            FROM rs_property_images as p
            WHERE p.RS_ITEM_ID = " . $itemID . "
            AND p.RS_PROPERTY_ID = " . $propertyID . "
            AND p.RS_CLIENT_ID = " . $clientID;

    $result = RSQuery($theQuery);
    return $result->fetch_assoc();
}

function getFile($clientID, $propertyID, $itemID) {
    $theQuery = "
            SELECT p.RS_DATA, p.RS_NAME, p.RS_SIZE
            FROM rs_property_files as p
            WHERE p.RS_ITEM_ID = " . $itemID . "
            AND p.RS_PROPERTY_ID = " . $propertyID . "
            AND p.RS_CLIENT_ID = " . $clientID;

    $result = RSQuery($theQuery);

    if(!$result) return false;

    return $result->fetch_assoc();
}

function convertData($fieldName, $fieldType) {
    if ($fieldType == 'date') {
        // date
        return 'IF(' . $fieldName . '="0000-00-00","",' . $fieldName . ')';
    } elseif ($fieldType == 'datetime') {
        // datetime
        return 'IF(' . $fieldName . '="0000-00-00 00:00:00","",' . $fieldName . ')';
    } elseif ($fieldType == 'float') {
        // float
        return 'TRIM(TRAILING "." FROM TRIM(TRAILING "0" FROM ' . $fieldName . '))';
    } else {
        // default
        return $fieldName;
    }
}

// Generates a comparison function for sorting multi-dimensional arrays with u*sort
function make_comparer() {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
                $criteria[$index] = is_array($criterion)
                        ? array_pad($criterion, 3, null)
                        : array($criterion, SORT_ASC, null);
        }

        return function($first, $second) use (&$criteria) {
                foreach ($criteria as $criterion) {
                        // How will we compare this round?
                        list($column, $sortOrder, $projection) = $criterion;
                        $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

                        // If a projection was defined project the values now
                        if ($projection) {
                                $lhs = call_user_func($projection, $first[$column]);
                                $rhs = call_user_func($projection, $second[$column]);
                        }
                        else {
                                $lhs = $first[$column];
                                $rhs = $second[$column];
                        }

                        // Do the actual comparison; do not return if equal
                        if ($lhs < $rhs) {
                                return -1 * $sortOrder;
                        }
                        else if ($lhs > $rhs) {
                                return 1 * $sortOrder;
                        }
                }

                return 0; // tiebreakers exhausted, so $first == $second
        };
}

function sortItemsInParent(&$arr) {

    for ($i = 1; $i < count($arr); $i++) {
        for ($j = 0; $j < $i; $j++) {
            if ($arr[$j]["nodeItemType"        ] == $arr[$i]["nodeItemType"        ] &&
                $arr[$j]["parentID"                ] == $arr[$i]["parentID"                ] &&
                $arr[$j]["parentItemType"    ] == $arr[$i]["parentItemType"    ] &&
                $arr[$j]["parentPropertyID"] == $arr[$i]["parentPropertyID"] &&
                (($arr[$j]["parentID"] != 0 && $arr[$j]["order"] < $arr[$i]["order"]) || ($arr[$j]["parentID"] == 0 && $arr[$j]["order"] > $arr[$i]["order"]))) {
                // swap
                array_splice($arr,$j,0,array_splice($arr,$i,1));
                break;
            }
        }
    }
}

function getMainPropertyID($itemTypeID, $clientID) {

    $theQuery = RSQuery("SELECT RS_MAIN_PROPERTY_ID FROM rs_item_types WHERE RS_ITEMTYPE_ID = " . $itemTypeID . " AND RS_CLIENT_ID = " . $clientID);

    if ($theQuery && $mainProperty = $theQuery->fetch_assoc()) {
        return $mainProperty['RS_MAIN_PROPERTY_ID'];
    } else {
        return '0';
    }
}

function getPropertyType($propertyID, $clientID) {
    $theQuery = RSQuery("SELECT RS_TYPE FROM rs_item_properties WHERE RS_PROPERTY_ID = " . parsePID($propertyID, $clientID) . " AND RS_CLIENT_ID = " . $clientID);

    if ($theQuery && $propertyRes = $theQuery->fetch_assoc())
        return $propertyRes['RS_TYPE'];

    RSError("RSMitemsManagement: getPropertyType: property not found: ".$propertyID);
    return '';
}

// TODO: Remove the itemTypeID variable from all the calls as it is not used
function getPropertyValue($PropertyName, $itemTypeID, $itemID, $clientID) {

    $propertyID = getClientPropertyID_RelatedWith_byName($PropertyName, $clientID);

    return getItemPropertyValue($itemID, $propertyID, $clientID);
}

function parsePID($propertyID, $clientID) {
    // If the propertyID is already numeric, return it
    if (is_numeric($propertyID))
        return $propertyID;

    // The propertyID is not numeric.
    // Let's suppose there is an application property name instead
    // of a numeric value. Try to extract the propertyID from it.
    return getClientPropertyID_RelatedWith_byName($propertyID, $clientID);
}

function parseITID($itemTypeID, $clientID) {
    // If the propertyID is already numeric, return it
    if (is_numeric($itemTypeID))
        return $itemTypeID;

    // The $itemTypeID is not numeric.
    // Let's suppose there is an application item type instead
    // of a numeric value. Try to extract the itemTypeID from it.
    return getClientItemTypeID_RelatedWith_byName($itemTypeID, $clientID);
}

function getMainPropertyValue($itemTypeID, $itemID, $clientID) {
    return getItemPropertyValue($itemID, getMainPropertyID($itemTypeID, $clientID), $clientID);
}

function getItemIDs($itemtypeID, $clientID) {
    //Query items
    $theQueryItem = "SELECT RS_ITEM_ID FROM rs_items WHERE RS_ITEMTYPE_ID=" . $itemtypeID . " AND RS_CLIENT_ID=" . $clientID;

    // Execute the query
    $results = RSQuery($theQueryItem);

    // Extract the results
    $itemsArray = array();

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $itemsArray[] = $row['RS_ITEM_ID'];
        }
    }

    return $itemsArray;
}

function setPropertyValue($propertyName, $itemTypeID, $itemID, $clientID, $value, $userID = 0) {

    // Get filter property id and type
    $propertyID = getClientPropertyID_RelatedWith_byName($propertyName, $clientID);

    return setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $value, '', $userID);

}

function setDataPropertyValue($propertyName, $itemTypeID, $itemID, $clientID, $name, $value, $userID = 0) {

    // Get filter property id and type
    $propertyID = getClientPropertyID_RelatedWith_byName($propertyName, $clientID);

    return setDataPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $name, $value, '', $userID);

}

// Return an associative array with two elements: "auditTrail" (0/1), "auditTrailDescription" (0/1)
function checkForAuditTrail($propertyID, $clientID) {

    // build query
    $theQuery = 'SELECT RS_AUDIT_TRAIL AS "auditTrail", RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED AS "auditTrailDescription" FROM rs_item_properties WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_PROPERTY_ID = ' . $propertyID;

    // execute query
    $result = RSQuery($theQuery);

    if (!$result) return false;

    // retrieve and return the result
    return $result->fetch_assoc();
}

//    0 - Success
// -1 - Identifier error: can't associate an item with itself through an identifier property
// -2 - User not valid: can't update the property. Maybe the variable is audited and the change is being performed by a script?
// -3 - Query error while updating the property
// -4 - Invalid property type
function setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $value, $propertyType = '', $userID = 0, $forceOrder = '') {
    global $propertiesTables, $auditTrailPropertiesTables, $RStoken;

    if (!is_numeric($propertyID)) {
        // Let's suppose there is an application property name instead
        // of a numeric value. Try to extract the propertyID from it.
        $temp = getPropertyIDs_usingSysName(array($propertyID), $clientID);
        $propertyID = $temp[0]['ID'];
    }

    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);

    // get the property old (current) value
    $previousValue = getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType, $itemTypeID);
    // Compare with the actual value of the property
    if ($value == $previousValue) return 0;

        // Values are different, so continue updating
    if ($propertyType == 'image' || $propertyType == 'file') return -4; // files and images are not updated this way

    if ($propertyType == 'identifier') {

        if (($itemTypeID == getClientPropertyReferredItemType($propertyID, $clientID)) && ($value == $itemID)) {
            // can't associate an item with itself (can't assign an identifier that's the same item ID)
            return -1;
        }
    } elseif ($propertyType == 'identifiers') {
        if ($value == "") {
            // Force the value to zero since a multiidentifier can't hold empty values
            $value = "0";
        }

        if (($itemTypeID == getClientPropertyReferredItemType($propertyID, $clientID)) && (in_array($itemID, explode(',', $value)))) {
            // can't associate an item with itself (the property must not contain an identifier that's the same item ID)
            return -1;
        }
    }

    // check if property is Audited in the Audit Trail
    $response = checkForAuditTrail($propertyID, $clientID);
    if ($response) {
        if ($response['auditTrail'] == 1) {

            if (($userID == 0 || $userID == "") && $RStoken == ""){
                return -2;
                // ERROR: you have to define a valid userID
            }
        }

        // build query
        if ($propertyType == 'identifier' || $propertyType == 'identifiers') {
            //manage order value
            if ($forceOrder == '') {
                $oldOrder = getPropertyOrder($itemID, $propertyID, $clientID, $propertyType, $itemTypeID);
                $newOrder = recalculateOrder($previousValue, $value, $oldOrder);
            } else {
                $newOrder = implode(',', array_fill(0, count(explode(',', $value)), $forceOrder));
            }

            $theQuery = 'REPLACE INTO ' . $propertiesTables[$propertyType] . ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_ORDER) VALUES (' . $clientID . ',' . $itemTypeID . ',' . $itemID . ',' . $propertyID . ',"' . $value . '","' . $newOrder . '")';
        } else {
            $theQuery = 'REPLACE INTO ' . $propertiesTables[$propertyType] . ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA) VALUES (' . $clientID . ',' . $itemTypeID . ',' . $itemID . ',' . $propertyID . ',"' . $value . '")';
        }

        // execute query
        if (RSQuery($theQuery)) {

            if ($response['auditTrail'] == 1) {
                // save the change into the Audit Trail table
                $theQuery = 'INSERT INTO ' . $auditTrailPropertiesTables[$propertyType] . ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_USER_ID, RS_TOKEN, RS_DESCRIPTION, RS_CHANGED_DATE, RS_INITIAL_VALUE, RS_FINAL_VALUE) VALUES (' . $clientID . ',' . $itemTypeID . ',' . $itemID . ',' . $propertyID . ',' . $userID . ',"' . $RStoken . '",NULL,"' . date('Y-m-d H:i:s') . '","' . $previousValue . '","' . $value . '")';

                $saveQuery = RSQuery($theQuery);

                if(!$saveQuery) return -3;
            }

            // We add the new item ID to the array of created itemIDs
            global $RSMupdatedItemIDs;
            if (!in_array($itemTypeID . "," . $itemID, $RSMupdatedItemIDs)) $RSMupdatedItemIDs[] = $itemTypeID . "," . $itemID;

            return 0;
        } else {
            return -3;
        }
    } else {
        return -3;
    }
}

//    0 - Success
// -1 - Identifier error: can't associate an item with itself through an identifier property
// -2 - User not valid: can't update the property
// -3 - Query error while updating the property
// -4 - Invalid property type
function setDataPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $name, $value, $propertyType = '', $userID = 0) {
    global $propertiesTables, $auditTrailPropertiesTables;

    if (!is_numeric($propertyID)) {
        // Let's suppose there is an application property name instead
        // of a numeric value. Try to extract the propertyID from it.
        $temp = getPropertyIDs_usingSysName(array($propertyID), $clientID);
        $propertyID = $temp[0]['ID'];
    }

    if ($propertyType == '')
        $propertyType = getPropertyType($propertyID, $clientID);

    // only files and images are updated this way
    if (($propertyType != 'image') && ($propertyType != 'file')) return -4;

    if ((substr($value, 0, 2) != '0x') && ($value != ''))
        $value = '0x' . $value;

        // Compare stored value with new proposed value
        $actualValue = "0x" . getItemDataPropertyValue($itemID, $propertyID, $clientID, $propertyType);
        if (($actualValue == "0x" && $value == "")) return 0;

    // If the value is still empty, enclose it in quotes in order to form a valid query
    if ($value == '') {
        $value = "''";
        $size = 0;
    } else {
        $size = (strlen($value) - 2) / 2;
    }

    // build query
    $theQuery = 'REPLACE INTO ' . $propertiesTables[$propertyType] .
    ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_NAME, RS_SIZE, RS_DATA) VALUES (' .
    $clientID . ',' .
    $itemTypeID . ',' .
    $itemID . ',' .
    $propertyID . ',"' .
    $name . '",' .
    $size . ',' .
    $value . ')';

    // execute query
    if (RSQuery($theQuery)) {
        // The data has been properly set
        // We add the new item ID to the array of created itemIDs
        global $RSMupdatedItemIDs;
        if (!in_array($itemTypeID . "," . $itemID, $RSMupdatedItemIDs)) $RSMupdatedItemIDs[] = $itemTypeID . "," . $itemID;

        // Clear the archive in the filesystem cache
        switch ($propertyType) {
            case 'image':
                    global $RSimageCache;
                    foreach(glob($RSimageCache . "/" . $clientID . "/" . $propertyID . "/img_" . $itemID . "_*") as $f) unlink($f);
                    break;

            case 'file':
                    global $RSfileCache;
                    foreach(glob($RSfileCache . "/" . $clientID . "/" . $propertyID . "/file_" . $itemID . "_*") as $f) unlink($f);
                    break;

            default:
                    RSError("RSMitemsManagement: setDataPropertyValueByID: Unknown archive type: " . $propertyType);
        }

        return 0;
    }

    return -3;

}

// add an identifier to the list
function addIdentifier($id, $itemTypeID, $itemID, $propertyID, $clientID, $userID = 0) {

    if (($itemTypeID == getClientPropertyReferredItemType($propertyID, $clientID)) && ($id == $itemID))
        return false;
    // can't associate an item with itself (can't add an identifier that's the same item ID)

    // get property type
    $propertyType = getPropertyType($propertyID, $clientID);

    // the property must be an "identifiers" property
    if ($propertyType != 'identifiers')
        return false;

    // get property value
    $propertyValue = getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType);

    // add the identifier
    if ($propertyValue == '') {
        $propertyValue = $id;
    } elseif (!in_array($id, explode(',', $propertyValue)))
        $propertyValue .= ',' . $id;

    // update value
    setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $propertyValue, $propertyType, $userID);

    return true;
}

// remove an identifier from the list
function removeIdentifier($id, $itemTypeID, $itemID, $propertyID, $clientID, $userID = 0) {

    // get property type
    $propertyType = getPropertyType($propertyID, $clientID);

    // the property must be an "identifiers" property
    if ($propertyType != 'identifiers')
        return false;

    // get property value
    $propertyValue = getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType);

    // remove the identifier
    $idsList = explode(',', $propertyValue);
    for ($i = 0; $i < count($idsList); $i++) {
        if ($id == $idsList[$i]) {
            for ($j = $i; $j < count($idsList) - 1; $j++) {
                $idsList[$j] = $idsList[$j + 1];
            }
            array_pop($idsList);
            break;
        }
    }

    // update value
    setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, implode(',', $idsList), $propertyType, $userID);

    return true;
}

// replace an identifier from the list
function replaceIdentifier($oldId, $newId, $itemTypeID, $itemID, $propertyID, $clientID, $userID = 0) {

    // get property type
    $propertyType = getPropertyType($propertyID, $clientID);

    // the property must be an "identifiers" property
    if ($propertyType != 'identifiers')
        return false;

    // get property value
    $propertyValue = getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType);

    // replace the identifier if found
    $idsList = explode(',', $propertyValue);

    $oldPos = array_search($oldId, $idsList);
    if ($oldPos !== false) {
        $idsList[$oldPos] = $newId;
    }

    // update value
    setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, implode(',', $idsList), $propertyType, $userID);

    return true;
}
// *********************************************
// ************** APP ITEM TYPES ***************
// *********************************************

// ----------- create, update, delete ----------

// Insert new application item type into the db
function createAppItemType($appItemTypeName) {
    RSQuery("INSERT INTO rs_item_type_app_definitions (RS_NAME) VALUES ('" . $appItemTypeName . "');");
}

// Update an application item type into the db
function updateAppItemType($appItemTypeID, $appItemTypeNewName) {
    RSQuery("UPDATE rs_item_type_app_definitions SET RS_NAME = '" . $appItemTypeNewName . "' WHERE RS_ID = '" . $appItemTypeID . "';");
}

// Delete an application item from the db (all relationships, and children properties, and their relationships, will also be deleted)
function deleteAppItemType($appItemTypeID) {
    // Get a list of the children properties and delete them and their relationships
    $properties = RSQuery("SELECT RS_ID FROM rs_properties_app_definitions WHERE RS_ITEM_TYPE_ID = '" . $appItemTypeID . "';");
    if ($properties) {
        while ($property = $properties->fetch_assoc())
        deleteAppProperty($property['RS_ID']);
    }

    RSQuery("DELETE FROM rs_item_type_app_definitions WHERE RS_ID = '" . $appItemTypeID . "';");
    RSQuery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = '" . $appItemTypeID . "';");
}

// ------------------- get ---------------------

// Return the name of the application item type passed
function getAppItemTypeName($appItemTypeID) {
    $result = RSQuery("SELECT RS_NAME FROM rs_item_type_app_definitions WHERE RS_ID = " . $appItemTypeID);

    if (!$result) return "";

    $appItemTypeName = $result->fetch_assoc();

    return $appItemTypeName['RS_NAME'];
}

// Return the list of application's properties associated with the application item type passed
function getAppItemTypeProperties($appItemTypeID) {
    $result = RSQuery("SELECT RS_ID AS 'id', RS_NAME AS 'propertyName' FROM rs_property_app_definitions WHERE RS_ITEM_TYPE_ID = " . $appItemTypeID);

    $propertiesList = array();

    if ($result) {
        while ($row = $result->fetch_assoc())
            $propertiesList[] = $row;
    }

    return $propertiesList;
}

// Return the ID of the item type $appItemTypeName
function getAppItemTypeIDByName($appItemTypeName) {
    $result = RSQuery('SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = "' . $appItemTypeName . '"');

    if ($result && $appItemTypeID = $result->fetch_assoc())
        return $appItemTypeID['RS_ID'];

    return '0';
}

// *********************************************
// ************** APP PROPERTIES ***************
// *********************************************

// ----------- create, update, delete ----------

// Insert new application property into the db
function createAppProperty($appPropertyName, $appItemTypeID) {
    RSQuery("INSERT INTO rs_property_app_definitions (RS_NAME, RS_ITEM_TYPE_ID) VALUES ('" . $appPropertyName . "', '" . $appItemTypeID . "');");
}

// Update an application property into the db
function updateAppProperty($appPropertyID, $appPropertyNewName, $appPropertyNewItemType) {
    RSQuery("UPDATE rs_property_app_definitions SET RS_NAME = '" . $appPropertyNewName . "', RS_ITEM_TYPE_ID = '" . $appPropertyNewItemType . "' WHERE RS_ID = '" . $appPropertyID . "';");
}

// Delete an application property from the db (all relationships also will be deleted)
function deleteAppProperty($appPropertyID) {
    RSQuery("DELETE FROM rs_property_app_definitions WHERE RS_ID = '" . $appPropertyID . "';");
    RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = '" . $appPropertyID . "';");
}

// ------------------- get ---------------------

// Return the name of the application property passed
function getAppPropertyName($appPropertyID) {

    $result = RSQuery("SELECT RS_NAME FROM rs_property_app_definitions WHERE RS_ID = " . $appPropertyID);

    if (!$result) return "";

    $appPropertyName = $result->fetch_assoc();

    return $appPropertyName['RS_NAME'];
}

// Return the type of the application property passed
function getAppPropertyType($appPropertyID) {

    $result = RSQuery("SELECT RS_TYPE FROM rs_property_app_definitions WHERE RS_ID = " . $appPropertyID);

    if (!$result) return "";

    $appPropertyType = $result->fetch_assoc();

    return $appPropertyType['RS_TYPE'];
}

// Return the item type ID of the application property passed
function getAppPropertyItemType($appPropertyID) {

    $result = RSQuery("SELECT RS_ITEM_TYPE_ID FROM rs_property_app_definitions WHERE RS_ID = " . $appPropertyID);

    if (!$result) return "";

    $appPropertyItemTypeID = $result->fetch_assoc();

    return $appPropertyItemTypeID['RS_ITEM_TYPE_ID'];
}

// Return the default value of the application property passed
function getAppPropertyDefaultValue($appPropertyID) {

    $result = RSQuery("SELECT RS_DEFAULTVALUE FROM rs_property_app_definitions WHERE RS_ID = " . $appPropertyID);

    if (!$result) return "";

    $appPropertyDefaultValue = $result->fetch_assoc();

    return $appPropertyDefaultValue['RS_DEFAULTVALUE'];
}

// Return the item type identified by the application property passed
function getAppPropertyReferredItemType($appPropertyID) {

    $result = RSQuery("SELECT RS_REFERRED_ITEMTYPE FROM rs_property_app_definitions WHERE RS_ID = " . $appPropertyID);

    if (!$result) return "";

    $appPropertyItemTypeID = $result->fetch_assoc();

    return $appPropertyItemTypeID['RS_REFERRED_ITEMTYPE'];
}

// Return the ID of the property $appPropertyName
function getAppPropertyIDByName($appPropertyName) {
    $result = RSQuery('SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = "' . $appPropertyName . '"');

    if ($result && $appPropertyID = $result->fetch_assoc())
        return $appPropertyID['RS_ID'];
    return '0';
}

// ************************************************
// ************** CLIENT ITEM TYPES ***************
// ************************************************

// Return the item types list
function getClientItemTypes($clientID, $list = '', $sort = true) {

    if ($sort) {
        $orderBy = 'ORDER BY RS_NAME';
    } else {
        $orderBy = '';
    }

    if ($list == '') {
        $theQuery = RSQuery('SELECT RS_ITEMTYPE_ID AS "ID", RS_NAME AS "name" FROM rs_item_types WHERE RS_CLIENT_ID = ' . $clientID . ' ' . $orderBy);
    } else {
        $theQuery = RSQuery('SELECT RS_ITEMTYPE_ID AS "ID", RS_NAME AS "name" FROM rs_item_types WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID IN (' . $list . ') ' . $orderBy);
    }

    $itemTypes = array();

    if ($theQuery) {
        while ($row = $theQuery->fetch_assoc()) {
            $itemTypes[] = $row;
        }
    }

    return $itemTypes;
}

// Return the name of the client item type passed
function getClientItemTypeName($clientItemTypeID, $clientID) {

    $result = RSQuery("SELECT RS_NAME FROM rs_item_types WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID);

    if (!$result) return '';

    if ($clientItemTypeName = $result->fetch_assoc())
        return $clientItemTypeName['RS_NAME'];

    return '';
}

// Return the icon of the client item type passed
function getClientItemTypeIcon($clientItemTypeID, $clientID) {

    $result = RSQuery("SELECT RS_ICON FROM rs_item_types WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $clientItemTypeName = $result->fetch_assoc()) {
        return bin2hex($clientItemTypeName['RS_ICON']);
    } else {
        return '';
    }
}

// Return the list of categories of the item type passed
function getClientItemTypeCategories($clientItemTypeID, $clientID) {

    $result = RSQuery("SELECT RS_CATEGORY_ID, RS_NAME, RS_ORDER FROM rs_categories WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID . " ORDER BY RS_ORDER");

    $categoriesList = array();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categoriesList[] = array('id' => $row['RS_CATEGORY_ID'], 'name' => $row['RS_NAME']);
        }
    }

    return $categoriesList;
}

// Return the list of properties of the item type passed (the category will be omitted)
function getClientItemTypeProperties($clientItemTypeID, $clientID, $avoidDuplicateProperty = 0) {
    $categoriesList = getClientItemTypeCategories($clientItemTypeID, $clientID);

    $propertiesList = array();
    foreach ($categoriesList as $category)
        $propertiesList = array_merge($propertiesList, getClientCategoryProperties($category['id'], $clientID, $avoidDuplicateProperty));

    return $propertiesList;
}

// Return the list of categorized properties of the item type passed
function getClientItemTypePropertiesExtended($clientItemTypeID, $clientID, $avoidDuplicateProperty = 0) {
    $categoriesList = getClientItemTypeCategories($clientItemTypeID, $clientID);

    $propertiesList = array();
    foreach ($categoriesList as $category){
        $propertiesList[] = array('category' => $category['name']);
        $propertiesList = array_merge($propertiesList, getClientCategoryProperties($category['id'], $clientID, $avoidDuplicateProperty));
    }

    return $propertiesList;
}

// Return the list of properties id of the item type passed
function getClientItemTypePropertiesId($clientItemTypeID, $clientID) {
    $propertiesList = getClientItemTypeProperties($clientItemTypeID, $clientID);

    $propertiesIdList = array();
    foreach ($propertiesList as $property) {
        $propertiesIdList[] = $property['id'];
        // push
    }
    return $propertiesIdList;
}

// ************************************************
// ************** CLIENT CATEGORIES ***************
// ************************************************

// Return the name of the category passed
function getClientCategoryName($clientCategoryID, $clientID) {

    $result = RSQuery("SELECT RS_NAME FROM rs_categories WHERE RS_CATEGORY_ID = '" . $clientCategoryID . "' AND RS_CLIENT_ID = '" . $clientID . "'");

    if (!$result) return "";

    $categoryName = $result->fetch_assoc();

    return $categoryName['RS_NAME'];
}

// Return the item type of the category passed
function getClientCategoryItemType($clientCategoryID, $clientID) {

    $result = RSQuery("SELECT RS_ITEMTYPE_ID FROM rs_categories WHERE RS_CATEGORY_ID = '" . $clientCategoryID . "' AND RS_CLIENT_ID = '" . $clientID . "'");

    if (!$result) return '0';

    $categoryItemType = $result->fetch_assoc();

    return $categoryItemType['RS_ITEMTYPE_ID'];
}

// Return the list of properties of the category passed
function getClientCategoryProperties($clientCategoryID, $clientID, $avoidDuplicateProperty = 0) {

    $query = "SELECT RS_PROPERTY_ID, RS_NAME, RS_TYPE, RS_ORDER FROM rs_item_properties WHERE RS_CATEGORY_ID = " . $clientCategoryID . " AND RS_CLIENT_ID = " . $clientID;

    if ($avoidDuplicateProperty <> 0) {
        $query = $query . " AND RS_AVOID_DUPLICATION = 0";
    }

    $query = $query . ' ORDER BY RS_ORDER';
    $result = RSQuery($query);

    $propertiesList = array();

    if ($result) {
        while ($row = $result->fetch_assoc())
            $propertiesList[] = array('id' => $row['RS_PROPERTY_ID'], 'name' => $row['RS_NAME'], 'type' => $row['RS_TYPE']);
    }

    return $propertiesList;
}

// ************************************************
// ************** CLIENT PROPERTIES ***************
// ************************************************

// A simply function that returns true if the property passed is an identifier
function isIdentifier($propertyID, $clientID, $typeName = '') {
    if ($typeName == '')
        $typeName = getPropertyType($propertyID, $clientID);

    return (($typeName == 'identifier') || ($typeName == 'identifiers') || ($typeName == 'identifier2itemtype') || ($typeName == 'identifier2property'));
}

// A simply function that returns true if the value passed (an identifier value) is a null value for identifiers, such as '0' or ''
function isNullIdentifier($value) {
    return ($value == '0' || $value == '');
}

function isSingleIdentifier($propertyType) {
    return ($propertyType == 'identifier');
}

function isMultiIdentifier($propertyType) {
    return ($propertyType == 'identifiers');
}

function isIdentifier2itemtype($propertyType) {
    return ($propertyType == 'identifier2itemtype');
}

function isIdentifier2property($propertyType) {
    return ($propertyType == 'identifier2property');
}

// Delete a client property from the db (all relationships also will be deleted)
function deleteClientProperty($propertyID, $clientID) {
    global $propertiesTables;

    // get item type
    $itemTypeID = getClientPropertyItemType($propertyID, $clientID);

    $propertyType = getClientPropertyType($propertyID, $clientID);

    if(RSQuery("DELETE FROM " . $propertiesTables[$propertyType] . " WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID) && ($property['type'] == 'image' || $property['type'] == 'file')){
		deleteMediaProperty($clientID,$propertyID);
	}
    RSQuery("DELETE FROM rs_item_properties WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID);
    RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID);
    RSQuery("DELETE FROM rs_properties_groups WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID);
    RSQuery("DELETE FROM rs_properties_lists    WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID);
    RSQuery("DELETE FROM rs_token_permissions WHERE RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID);

    if ($propertyID == getMainPropertyID($itemTypeID, $clientID)) {
        // reset main value
        RSQuery('UPDATE rs_item_types SET RS_MAIN_PROPERTY_ID = 0 WHERE RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID);
    }
}

// Return the name of the property passed
function getClientPropertyName($clientPropertyID, $clientID) {

    $result = RSQuery("SELECT RS_NAME FROM rs_item_properties WHERE RS_PROPERTY_ID = " . ParsePID($clientPropertyID, $clientID) . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $propertyName = $result->fetch_assoc()) {
        return $propertyName['RS_NAME'];
    } else {
        return '';
    }
}

// Return the type of the property passed
function getClientPropertyType($clientPropertyID, $clientID) {

    $result = RSQuery("SELECT RS_TYPE FROM rs_item_properties WHERE RS_PROPERTY_ID = " . $clientPropertyID . " AND RS_CLIENT_ID = " . $clientID);

    if (!$result) return '';

    $propertyType = $result->fetch_assoc();

    return $propertyType['RS_TYPE'];
}

// Return the default value of the property passed
function getClientPropertyDefaultValue($clientPropertyID, $clientID) {

    // check related application property default value
    $appPropertyID = getAppPropertyID_RelatedWith($clientPropertyID, $clientID);

    $appPropertyDefaultValue = null;
    if ($appPropertyID != '0') $appPropertyDefaultValue = getAppPropertyDefaultValue($appPropertyID);

    if ($appPropertyDefaultValue != null) {
        // the client property is related and the application property default value (that has priority) exists, so return it
        return $appPropertyDefaultValue;
    } else {
        // the client property is not related or the application property default value is null, so return the client property default value
        $result = RSQuery("SELECT RS_DEFAULTVALUE FROM rs_item_properties WHERE (RS_PROPERTY_ID = " . $clientPropertyID . " AND RS_CLIENT_ID = " . $clientID . ")");

        if (!$result) return '';

        $propertyDefaultValue = $result->fetch_assoc();

        return $propertyDefaultValue['RS_DEFAULTVALUE'];
    }
}

// Return the ID of the category of the client's property passed
function getClientPropertyCategory($clientPropertyID, $clientID) {
    $clientPropertyID = ParsePID($clientPropertyID, $clientID);

    $result = RSQuery("SELECT RS_CATEGORY_ID FROM rs_item_properties WHERE RS_PROPERTY_ID = " . $clientPropertyID . " AND RS_CLIENT_ID = " . $clientID);

    if (!$result) return '';

    $category = $result->fetch_assoc();
    return $category['RS_CATEGORY_ID'];
}

// Return the ID of the item type of the client's property passed
function getClientPropertyItemType($clientPropertyID, $clientID) {
    return getClientCategoryItemType(getClientPropertyCategory($clientPropertyID, $clientID), $clientID);
}

// Return the ID of the item type identified (property ID is the ID of an identifier client property)
function getClientPropertyReferredItemType($propertyID, $clientID) {

    // prepare first query to get the user referred itemtype
    $theQuery = 'SELECT RS_REFERRED_ITEMTYPE FROM rs_item_properties WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_PROPERTY_ID = ' . $propertyID;

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $row = $result->fetch_assoc())
        if (($row['RS_REFERRED_ITEMTYPE'] != '') && ($row['RS_REFERRED_ITEMTYPE'] != '0'))
            return $row['RS_REFERRED_ITEMTYPE'];

    // prepare second query to get the system referred itemtype
    $theQuery = 'SELECT rs_item_type_app_relations.RS_ITEMTYPE_ID AS "itemTypeID" FROM rs_property_app_relations INNER JOIN rs_property_app_definitions ON (rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) INNER JOIN rs_item_type_app_relations ON (rs_property_app_definitions.RS_REFERRED_ITEMTYPE = rs_item_type_app_relations.RS_ITEMTYPE_APP_ID) WHERE (rs_property_app_relations.RS_PROPERTY_ID = ' . $propertyID . ' AND rs_property_app_relations.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_type_app_relations.RS_CLIENT_ID = ' . $clientID . ')';

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $row = $result->fetch_assoc()) return $row['itemTypeID'];

    return 0;
}

// Return the ID of the item type identified (appPropertyName is the name of an identifier application property)
function getClientPropertyReferredItemType_byName($appPropertyName, $clientID) {
    return getClientItemTypeID_RelatedWith(getAppPropertyReferredItemType(getAppPropertyIDByName($appPropertyName)), $clientID);
}

// Translate the value of a multi-identifiers property
function translateMultiIdentifier($propertyID, $propertyValue, $clientID) {

    if (isNullIdentifier($propertyValue))
        return '';

    // get the referred item type
    $referredItemTypeID = getClientPropertyReferredItemType($propertyID, $clientID);

    // get the main property ID
    $mainPropertyID = getMainPropertyID($referredItemTypeID, $clientID);

    // retrieve values
    $vals = explode(',', $propertyValue);

    // start translating
    $newValue = array();
    for ($i = 0; $i < count($vals); $i++)
        $newValue[] = getItemPropertyValue($vals[$i], $mainPropertyID, $clientID);

    // return the final value
    return implode('; ', $newValue);
}

// Translate the value of a single identifier property
function translateSingleIdentifier($propertyID, $propertyValue, $clientID, $propertyType = '') {

    if (isNullIdentifier($propertyValue))
        return '';

    if ($propertyType == '') {
        // retrieve property type
        $propertyType = getPropertyType($propertyID, $clientID);
    }

    if ($propertyType == 'identifier') {
        // get the referred item type
        $referredItemTypeID = getClientPropertyReferredItemType($propertyID, $clientID);

        // return the value
        return getItemPropertyValue($propertyValue, getMainPropertyID($referredItemTypeID, $clientID), $clientID);
    } elseif ($propertyType == 'identifier2itemtype') {

        // return the value
        return getClientItemTypeName($propertyValue, $clientID);
    } elseif ($propertyType == 'identifier2property') {

        // return the value
        return getClientPropertyName($propertyValue, $clientID);
    }
}

// Return true if the property is visible for the user passed
function isPropertyVisible($userID, $propertyID, $clientID) {
    $propertyID = ParsePID($propertyID, $clientID);

    $groups = RSQuery("SELECT rs_properties_groups.RS_PROPERTY_ID FROM rs_properties_groups INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_properties_groups.RS_PROPERTY_ID = " . $propertyID . " AND rs_properties_groups.RS_CLIENT_ID = " . $clientID . ") AND (rs_users_groups.RS_USER_ID = " . $userID . " AND rs_users_groups.RS_CLIENT_ID = " . $clientID . ") LIMIT 1");

    if ($groups->num_rows > 0) return true;

    return false;
}

// Return true if the property is visible for the user passed
function arePropertiesVisible($userID, $propertyIDs, $clientID) {
    foreach ($propertyIDs as $propertyID) {
        if (!isPropertyVisible($userID, $propertyID, $clientID)) return false;
    }

    return true;
}

// Returns a list of visible properties for the itemtype and user passed
function getVisibleProperties($itemTypeID, $clientID, $userID, $sort = false) {
    $orderBy = '';

    if ($sort) {
        $orderBy = 'ORDER BY rs_item_properties.RS_ORDER';
    }

    // build query
    $theQuery = 'SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID AS "propertyID" FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ') ' . $orderBy;

    // execute query
    $result = RSQuery($theQuery);

    $properties = array();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row['propertyID'];
        }
    }

    return $properties;
}

// Returns a list of visible properties (ID and name) for the itemtype and user passed
function getVisibleProperties_extended($itemTypeID, $clientID, $userID, $withName, $withType, $withDefaultValue, $sort = false, $onlySearchable = false) {

    if ($withName) { $getNamePart = ', rs_item_properties.RS_NAME AS "name"';
    } else { $getNamePart = '';
    }
    if ($withType) { $getTypePart = ', rs_item_properties.RS_TYPE AS "type"';
    } else { $getTypePart = '';
    }
    if ($withDefaultValue) { $getDefValuePart = ', rs_item_properties.RS_DEFAULTVALUE AS "defaultValue"';
    } else { $getDefValuePart = '';
    }

    if ($onlySearchable) { $whereSearchablePart = ' AND (rs_item_properties.RS_SEARCHABLE = 1)';
    } else { $whereSearchablePart = '';
    }

    if ($sort) {
        $orderBy = 'ORDER BY rs_item_properties.RS_ORDER';
    } else {
        $orderBy = '';
    }

    // build query
    $theQuery = 'SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID AS "ID"' . $getNamePart . $getTypePart . $getDefValuePart . ' FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ')'.$whereSearchablePart.' AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ') ' . $orderBy;

    // execute query
    $result = RSQuery($theQuery);

    $properties = array();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }

    return $properties;
}

// Returns all visible properties (and categories) for a given itemtype and a user
function getUserVisibleProperties($itemTypeID, $clientID, $userID) {
    $result = RSQuery('SELECT DISTINCT rs_categories.RS_CATEGORY_ID AS "categoryID", rs_categories.RS_NAME AS "categoryName", rs_item_properties.RS_PROPERTY_ID AS "propertyID", rs_item_properties.RS_NAME AS "propertyName", rs_item_properties.RS_TYPE AS "propertyType", rs_item_properties.RS_DEFAULTVALUE AS "propertyDefaultValue" FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ') ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER');
    $results = array();

    if ($result) {
        while ($row = $result->fetch_assoc())
            $results[] = $row;
    }

    return $results;
}

// Returns all visible property IDs for a given itemtype and user
function getUserVisiblePropertiesIDs($itemTypeID, $clientID, $userID) {
    $result = RSQuery('SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID AS "propertyID" FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ')');
    $results = array();
    if ($result) {
        while ($row = $result->fetch_assoc())
            $results[] = $row['propertyID'];
    }

    return $results;
}

// Returns all visible properties for a user
function getAllVisibleProperties($clientID, $userID, $sort = false) {
    $orderBy = '';
    if ($sort)
        $orderBy = 'ORDER BY rs_item_properties.RS_NAME';

    // build query
    $theQuery = 'SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID AS "ID", rs_item_properties.RS_NAME AS "name" FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ') ' . $orderBy;

    // execute query
    $result = RSQuery($theQuery);

    $properties = array();

    if ($result) {
        while ($row = $result->fetch_assoc())
            $properties[] = $row;
    }

    return $properties;
}

// ************************************************
// **************** CLIENT ITEMS ******************
// ************************************************

// Get items with their main values
function getItems($itemTypeID, $clientID, $sort = true, $ids = '', $limit = '') {
    $result = IQ_getItems($itemTypeID, $clientID, $sort, $ids, $limit);

    $items = array();

    if ($result) {
        while ($row = $result->fetch_assoc())
            $items[] = $row;
    }

    return $items;
}

// Return an items tree, represented by an associative array: parentNode[parentItemID] = array(childItemID1, childItemID2,...); ...
// linkPropertyID : a property that associate an item with another; for example, it could be a parentID property, that associate a parent to the item
// linkValue : the start link value; for example, it could be the ID of the root item of an item tree hierarchy
// extraFilters: the extra filters for the items search
// extraProperties: the extra properties to return
// orderBy: the level nodes sort property: this is not exactly an order for the tree, but for the levels... giving an order to the
//                    levels assure that the list of childs, for each parent node, it will be sorted by "orderBy" property
function getItemsTree($itemTypeID, $clientID, $linkPropertyID, $linkValue, $extraFilters = array(), $extraProperties = array(), $orderBy = '', $results = null) {

    // init filter properties and return properties arrays
    $filterProperties = $extraFilters;
    $returnProperties = $extraProperties;

    // filter items by parent(s)
    if (strpos($linkValue, ',') === false) {
        $filterProperties[] = array('ID' => $linkPropertyID, 'value' => $linkValue);
    } else {
        $filterProperties[] = array('ID' => $linkPropertyID, 'value' => $linkValue, 'mode' => '<-IN');
    }

    // return the parent(s)
    $returnProperties[] = array('ID' => $linkPropertyID, 'name' => 'parent');

    // get the level nodes
    $levelNodes = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy);

    if (count($levelNodes) > 0) {
        // get the next level nodes
        foreach ($levelNodes as $node) {
            $results[$node['parent']][] = $node;
            $nextLinkValueArr[] = $node['ID'];
        }
        return getItemsTree($itemTypeID, $clientID, $linkPropertyID, implode(',', $nextLinkValueArr), $extraFilters, $extraProperties, $orderBy, $results);
    } else {
        // there are no more levels...
        return $results;
    }
}

// return true if the item is found into the subtree
function inSubTree($itemID, $subtree) {

    if ($subtree != null) {
        foreach ($subtree as $node) {
            foreach ($node as $child) {
                if ($itemID == $child['ID']) {
                    // item found
                    return true;
                }
            }
        }
    }

    return false;
}

// Create a new item in blank
function createEmptyItem($itemTypeID, $clientID) {
    // get id for new item
    $newID = getNextItemTypeIdentification($itemTypeID, $clientID);

    // In order to prevent several calls to this function from different PHPs to return the same newID
    // for new items or that the last ID stored for the itemtype is incorrect because of item imports
    // we ensure that the INSERT query is successfully executed before continuing.
    // If the query is not successfully executed, it is because the newID already exists in the DB
    // so we will calculate a new ID and then we will try the creation again, until we get a success
    // get id for new item
    // As we know that the query will fail eventually until a valid ID is found, we won't track query execution errors
    while(!RSQuery('INSERT INTO rs_items ' . '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_CLIENT_ID) ' . 'VALUES ' . '(' . $itemTypeID . ',' . $newID . ',' . $clientID . ')', false)) {
        $newID = getNextIdentification('rs_items', 'RS_ITEM_ID', $clientID, array('RS_ITEMTYPE_ID' => $itemTypeID));
    }

    // Update the item type with the latest ID created for the item
    RSquery('UPDATE rs_item_types SET RS_LAST_ITEM_ID = ' . $newID . ' WHERE    RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID);

    return $newID;
}

// Create a new item
function createItem($clientID, $propertiesValues = array(), $itemTypeID = "0") {
    global $propertiesTables;

                //$mongodata = [];

    foreach ($propertiesValues as $property) {
        $propertiesID[] = $property['ID'];
    }

                if ($itemTypeID == "0") $itemTypeID = getItemTypeIDFromProperties($propertiesID, $clientID);

    $newID = createEmptyItem($itemTypeID, $clientID);

    // insert the properties
    $propertiesList = getClientItemTypeProperties($itemTypeID, $clientID);

    foreach ($propertiesList as $property) {

        for ($i = 0; $i < count($propertiesValues); $i++) {
            // We search the property values to create in order to obtain the matching item propertyID
            if ($property['id'] == $propertiesValues[$i]['ID']) break;
        }

        if ($i < count($propertiesValues)) {
            // set the passed value for the property
            $value = $propertiesValues[$i]['value'];

        } else {
            // set the default value for the property
            $value = getClientPropertyDefaultValue($property['id'], $clientID);
        }

        // finally insert the property
        _dbInsertItemPropertyValue($itemTypeID, $newID, $property['id'], $property['type'], $value, $clientID);

                                //Add it to our mongo data array
                                //$mongodata[] = [$property['id'] => $value];

    }

    // We add the new item ID to the array of created itemIDs
    global $RSMcreatedItemIDs;
    $RSMcreatedItemIDs[] = $itemTypeID . "," . $newID;

                // We also create the item in MongoDB
                //global $MongoDB;
                //$MongoDB->SetDatabase($clientID);
                //$MongoDB->SetCollection($itemTypeID);
                //$MongoDB->Insert($mongodata);

    return $newID;
}

// Make copies of the item passed
function duplicateItem($itemTypeID, $itemIDs, $clientID, $numCopies = 1, $descendants = array(), &$copiedItems = array(), &$itemTypeProperties = array()) {
    global $propertiesTables, $RSuserID;

    if ($numCopies < 1) return -1;

    $originalItemIDs = explode(",",$itemIDs);

    if (!array_key_exists($itemTypeID, $copiedItems)) $copiedItems[$itemTypeID] = array();

    // retrieve the first ID available
    $nextIDAvailable = getNextItemTypeIdentification($itemTypeID, $clientID);

    // build the query to duplicate item
    $theQuery_duplicateItem = 'INSERT INTO rs_items (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_CLIENT_ID) VALUES ';

    for ($i = 0; $i < count($originalItemIDs); $i++) {
        for ($j = 0; $j < $numCopies; $j++) {
            $newItemsIDs[$originalItemIDs[$i]][$j] = $nextIDAvailable++;

            // save the IDs into an array to return
            $theQuery_duplicateItem .= '(' . $itemTypeID . ',' . $newItemsIDs[$originalItemIDs[$i]][$j] . ',' . $clientID . '),';
        }
    }

    // remove last comma and execute query
    if (!RSQuery(substr($theQuery_duplicateItem, 0, -1))) return -1;

    // retrieve item properties
    if(!array_key_exists($itemTypeID, $itemTypeProperties)) {
        $itemTypeProperties[$itemTypeID] = getClientItemTypeProperties($itemTypeID, $clientID, 1);
    }

    foreach ($itemTypeProperties[$itemTypeID] as $property) {
        // retrieve the item property value to copy
        $propertyOrders = array();
        $propertyValues = getItemsPropertyValues($property['id'], $clientID, $itemIDs, $property['type'], $itemTypeID, false, 1, $propertyOrders);

        if ($property['type'] == 'image' || $property['type'] == 'file') {
            // build the query to insert properties for the items duplicated
            $theQuery_copyProperties = 'INSERT INTO ' . $propertiesTables[$property['type']] . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_NAME, RS_SIZE, RS_DATA, RS_CLIENT_ID) VALUES ';

            for ($i = 0; $i < count($originalItemIDs); $i++) {
                // retrieve the item property value to copy
                $propertyData = getItemDataPropertyValue($originalItemIDs[$i], $property['id'], $clientID, $property['type'], $itemTypeID);
                $propertyImageValues = explode(":", $propertyValues[$originalItemIDs[$i]]);

                for ($j = 0; $j < $numCopies; $j++) {
                    $theQuery_copyProperties .= '(' . $itemTypeID . ',' . $newItemsIDs[$originalItemIDs[$i]][$j] . ',' . $property['id'] . ',"' . $propertyImageValues[0] . '",' . $propertyImageValues[1] . ',0x' . $propertyData . ',' . $clientID . '),';
                }
            }

        } elseif ($property['type'] == 'identifier' || $property['type'] == 'identifiers') {
            // build the query to insert properties for the items duplicated
            $theQuery_copyProperties = 'INSERT INTO ' . $propertiesTables[$property['type']] . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID, RS_ORDER) VALUES ';

            for ($i = 0; $i < count($originalItemIDs); $i++) {
                for ($j = 0; $j < $numCopies; $j++) {
                    $theQuery_copyProperties .= '(' . $itemTypeID . ',' . $newItemsIDs[$originalItemIDs[$i]][$j] . ',' . $property['id'] . ',"' . $propertyValues[$originalItemIDs[$i]] . '",' . $clientID . ',"' . $propertyOrders[$originalItemIDs[$i]] . '"),';
                }
            }

        } else {
            // build the query to insert properties for the items duplicated
            $theQuery_copyProperties = 'INSERT INTO ' . $propertiesTables[$property['type']] . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID) VALUES ';

            for ($i = 0; $i < count($originalItemIDs); $i++) {
                for ($j = 0; $j < $numCopies; $j++) {
                    $theQuery_copyProperties .= '(' . $itemTypeID . ',' . $newItemsIDs[$originalItemIDs[$i]][$j] . ',' . $property['id'] . ',"' . $propertyValues[$originalItemIDs[$i]] . '",' . $clientID . '),';
                }
            }
        }

        // remove last comma and execute query
        RSQuery(substr($theQuery_copyProperties, 0, -1));
    }

    // Update the item type with the latest ID created for the item
    RSquery('UPDATE rs_item_types SET RS_LAST_ITEM_ID = ' . $newItemsIDs[array_keys($newItemsIDs)[count($originalItemIDs)-1]][$numCopies - 1] . ' WHERE RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID);

    // Save the original item and its copies ids to avoid repeating
    $copiedItems[$itemTypeID] += $newItemsIDs;

    //duplicate descendants
    if (isset($descendants[$itemTypeID])) {
        foreach ($descendants[$itemTypeID] as $descendant) {
            // retrieve the child item(s) of original item by current descendant relation
            // Ckeck for recursivity
            if (array_key_exists($descendant[0],$descendants)) {
                $recursivePosition = array_search_ID($descendant[0], $descendants[$descendant[0]], 0);
            } else {
                $recursivePosition = false;
            }
            if ($recursivePosition !== false){
                $recursiveProperty = $descendants[$descendant[0]][$recursivePosition][1];
            } else {
                $recursiveProperty = getRecursivePropertyID($descendant[0], $clientID);
            }
            $propertyType = getPropertyType($descendant[1], $clientID);
            if ($recursiveProperty != '0' && $recursiveProperty != $descendant[1]) {
                // The itemtype is recursive and we're looking for a different relation/property so only items in root level of recursivity should have a value in this property (other levels will be copied under their recursive parent)
                // Check if the recursive property is in descendants lists and add it otherwise
                if ($recursivePosition === false) {
                    $descendants[$descendant[0]][] = array($descendant[0],$recursiveProperty);
                }
            }
            if($propertyType == 'identifier') {
                //we can search for descendants of all items together
                if($descendant[1]!='' && $descendant[1]!='0') {
                    $filterProperties = array();
                    $returnProperties = array();
                    $filterProperties[] = array('ID' => $descendant[1], 'value' => $itemIDs, 'mode' => '<-IN');
                    $returnProperties[] = array('ID' => $descendant[1], 'name' => 'parent');
                    $childs = getFilteredItemsIDs($descendant[0], $clientID, $filterProperties, $returnProperties);
                } else {
                    $childs = array();
                }
            } elseif($propertyType == 'identifiers') {
                //we have to search each source item descendants separatedly
                $childs = array();
                foreach($originalItemIDs as $itemID){
                    if($descendant[1]!='' && $descendant[1]!='0') {
                        $filterProperties = array();
                        $returnProperties = array();
                        $filterProperties[] = array('ID' => $descendant[1], 'value' => $itemID, 'mode' => 'IN');
                        $tmpChilds = getFilteredItemsIDs($descendant[0], $clientID, $filterProperties, $returnProperties);
                        foreach($tmpChilds as $childKey => $childValue){
                            $tmpChilds[$childKey]['parent'] = $itemID;
                        }
                        $childs = array_merge($childs, $tmpChilds);
                    }
                }
            } else {
                //unexpected property type
                return -1;
            }

            $childsToCopy = array();
            $childsToMove = array();
            foreach ($childs as $child) {
                // Check if the item has been already copied
                if (array_key_exists($descendant[0], $copiedItems) && array_key_exists($child['ID'], $copiedItems[$descendant[0]])) {
                    // As the item has already been copied we move current property to parent in new branch
                    if(array_key_exists($child['ID'],$childsToMove)) {
                        $childsToMove[$child['ID']] = array();
                    }
                    $childsToMove[$child['ID']][] = $child['parent'];

                } else {
                    // Check if the item is a copy already created from another relation of current proccess and do nothing in this case (will be updated/moved to parent in new branch once original item is detected)
                    if (!array_key_exists($descendant[0], $copiedItems) || !in_array_recursive($child['ID'], $copiedItems[$descendant[0]])) {
                        // The item has not already been copied so copy it
                        // We save the item an the parent it depends of in case there are multiples
                        if(!array_key_exists($child['ID'],$childsToMove)) {
                            $childsToMove[$child['ID']] = array();
                        }
                        $childsToMove[$child['ID']][] = $child['parent'];

                        if(!in_array($child['ID'],$childsToCopy)) {
                            $childsToCopy[] = $child['ID'];
                        }
                    }
                }
            }

            if (count($childsToCopy) > 0) {
                if (duplicateItem($descendant[0], implode(',', $childsToCopy), $clientID, $numCopies, $descendants, $copiedItems, $itemTypeProperties) == -1) {
                    return -1;
                }
            }

            foreach ($childsToMove as $originalChildID => $parents) {
                for ($j = 0; $j < $numCopies; $j++) {
                //foreach ($copiedItems[$descendant[0]][$originalChildID] as $copiedChildID) {
                    foreach ($parents as $parent) {
                        // change parent of new child items to corresponding duplicated item
                        // Try to replace only the old parent with new one in the property and if it fails simply overwrite property with new value
                        if (!replaceIdentifier($parent, $newItemsIDs[$parent][$j], $descendant[0], $copiedItems[$descendant[0]][$originalChildID][$j], $descendant[1], $clientID, $RSuserID)) {
                            setPropertyValueByID($descendant[1], $descendant[0], $copiedItems[$descendant[0]][$originalChildID][$j], $clientID, $newItemsIDs[$parent][$j], '', $RSuserID);
                        }
                    }
                }
            }
        }
    }

    while (is_array($newItemsIDs) && count($newItemsIDs) == 1) $newItemsIDs = $newItemsIDs[array_keys($newItemsIDs)[0]];

    return $newItemsIDs;
}

// Loop recursively over all the items of haystack (in all dimensions) searching for needle and return true if found it
function in_array_recursive($needle, $haystack) {
    if (is_array($haystack)) {
        foreach ($haystack as $item) {
            if(in_array_recursive($needle, $item)) {
                return true;
            }
        }
    } else {
        if ($needle == $haystack) {
            return true;
        }
    }
    return false;
}

// Delete an item and all of his defined properties
function deleteItem($itemTypeID, $itemID, $clientID, $descendants = array()) {
    global $definitions;

    // delete item from rs_items table
    RSQuery("DELETE FROM rs_items WHERE RS_ITEMTYPE_ID = " . $itemTypeID . " AND RS_ITEM_ID = " . $itemID . " AND RS_CLIENT_ID = " . $clientID);

    // delete all item properties values
    $propertiesList = getClientItemTypeProperties($itemTypeID, $clientID);
    foreach ($propertiesList as $property) {
        deleteItemPropertyValue($itemTypeID, $itemID, $property['id'], $clientID, $property['type']);
    }

    $removedProperties = array();
    //remove passed descendants
    if (isset($descendants[$itemTypeID])) {
        foreach ($descendants[$itemTypeID] as $descendant) {
            // Add property to removed list
            $removedProperties[]=$descendant[0].",".$descendant[1];
            // retrieve the child item(s) of original item by current descendant relation
            $filterProperties = array(array('ID' => $descendant[1], 'value' => $itemID, 'mode' => 'IN'));
            $returnProperties = array();
            $childs = getFilteredItemsIDs($descendant[0], $clientID, $filterProperties, $returnProperties);

            if (count($childs) == 1) {
                deleteItem($descendant[0], $childs[0]['ID'], $clientID, $descendants);
            } elseif (count($childs) > 1) {
                deleteItems($descendant[0], $clientID, implode(',', array_column($childs, 'ID')), $descendants);
            }
        }
    }

    // --- remove the item from the identifiers properties---
    // get item types
    $itemTypes = getClientItemTypes($clientID);

    foreach ($itemTypes as $itemType) {
        // get item type properties
        $propertiesList = getClientItemTypeProperties($itemType['ID'], $clientID);

        foreach ($propertiesList as $property) {
            // if we have already removed the descendants related by this property don't proccess again
            if (!in_array($itemType['ID'].",".$property['id'],$removedProperties)) {
                if (isSingleIdentifier($property['type'])) {
                    if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                        // reset identifier property for the items
                        RSQuery('UPDATE rs_property_identifiers SET RS_DATA = 0 WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND RS_DATA = ' . $itemID);
                    }
                } elseif (isMultiIdentifier($property['type'])) {
                    if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                        // reset identifiers property for the items
                        $theQuery = RSQuery('SELECT RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA FROM rs_property_multiIdentifiers WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND FIND_IN_SET("' . $itemID . '", RS_DATA) > 0');

                        if ($theQuery) {
                            while ($row = $theQuery->fetch_assoc()) {
                                $old = explode(',', $row['RS_DATA']);
                                $new = array();
                                for ($k = 0; $k < count($old); $k++) {
                                    if ($old[$k] != $itemID) { $new[] = $old[$k];
                                    }
                                }
                                RSQuery('UPDATE rs_property_multiIdentifiers SET RS_DATA = "' . implode(',', $new) . '" WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $row['RS_ITEMTYPE_ID'] . ' AND RS_ITEM_ID = ' . $row['RS_ITEM_ID'] . ' AND RS_PROPERTY_ID = ' . $row['RS_PROPERTY_ID']);
                            }
                        }
                    }
                }
            }
        }
    }

    // delete the user associations, if any
    if ($itemTypeID == getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID)) {
        RSQuery('UPDATE rs_users SET RS_ITEM_ID = 0 WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEM_ID = ' . $itemID);
    }

    // We add the new item ID to the array of created itemIDs
    global $RSMdeletedItemIDs, $RSMupdatedItemIDs;
    if (!in_array($itemTypeID . "," . $itemID, $RSMdeletedItemIDs)) {
        $RSMdeletedItemIDs[] = $itemTypeID . "," . $itemID;
        // Remove also the item from the update arrays,
        // it could be there because the properties are being deleted
        if (($key = array_search($itemTypeID . "," . $itemID, $RSMupdatedItemIDs)) !== false) {
                unset($RSMupdatedItemIDs[$key]);
        }
    }
}

// Delete items and all of their defined properties
function deleteItems($itemTypeID, $clientID, $ids = '', $descendants = array()) {
    global $definitions, $propertiesTables;

    if ($ids != '') {
        $inClause = 'AND RS_ITEM_ID IN (' . $ids . ')';
    } else {
        $inClause = '';
    }

    // delete items from rs_items table
    RSQuery('DELETE FROM rs_items WHERE RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID . ' ' . $inClause);

    // delete all items properties values
    $propertiesList = getClientItemTypeProperties($itemTypeID, $clientID);

    foreach ($propertiesList as $property) {
        if(RSQuery('DELETE FROM ' . $propertiesTables[$property['type']] . ' WHERE RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' ' . $inClause) && ($property['type'] == 'image' || $property['type'] == 'file')){
            $itemIDs = explode(",",$ids);
            foreach ($itemIDs as $itemID) {
                deleteMediaFile($clientID,$itemID,$property['id']);
            }
    	}
    }

    $idsArr = explode(',', $ids);

    $removedProperties = array();
    //remove passed descendants
    if (isset($descendants[$itemTypeID])) {
        foreach ($descendants[$itemTypeID] as $descendant) {
            // Add property to removed list
            $removedProperties[]=$descendant[0].",".$descendant[1];
            if ($ids != '') {
                // retrieve the child item(s) of original item by current descendant relation
                $filterProperties = array();
                foreach ($idsArr as $id) {
                    $filterProperties[] = array('ID' => $descendant[1], 'value' => $id, 'mode' => 'IN');
                }
                $returnProperties = array();
                $childs = getFilteredItemsIDs($descendant[0], $clientID, $filterProperties, $returnProperties, '', false, '', '', 'OR');

                if (count($childs) == 1) {
                    deleteItem($descendant[0], $childs[0]['ID'], $clientID, $descendants);
                } elseif (count($childs) > 1) {
                    deleteItems($descendant[0], $clientID, implode(',', array_column($childs, 'ID')), $descendants);
                }
            } else {
                // No items list received, so delete all descendants too!!!
                deleteItems($descendant[0], $clientID, '', $descendants);
            }
        }
    }

    // --- remove the item from the identifiers properties ---
    // get item types
    $itemTypes = getClientItemTypes($clientID);

    foreach ($itemTypes as $itemType) {
        // get item type properties
        $propertiesList = getClientItemTypeProperties($itemType['ID'], $clientID);

        foreach ($propertiesList as $property) {
            // if we have already removed the descendants related by this property don't proccess again
            if (!in_array($itemType['ID'].",".$property['id'],$removedProperties)) {
                if (isSingleIdentifier($property['type'])) {
                    if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                        if ($ids != '') {
                            RSQuery('UPDATE rs_property_identifiers SET RS_DATA = 0 WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND RS_DATA IN (' . $ids . ')');
                        } else {
                            RSQuery('UPDATE rs_property_identifiers SET RS_DATA = 0 WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id']);
                        }
                    }
                } elseif (isMultiIdentifier($property['type'])) {
                    if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                        if ($ids != '') {
                            $conditions = array();
                            foreach ($idsArr as $id) {
                                $conditions[] = 'FIND_IN_SET("' . $id . '", RS_DATA) > 0';
                            }
                            $theQuery = RSQuery('SELECT RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA FROM rs_property_multiIdentifiers WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND (' . implode(' OR ', $conditions) . ')');
                            if ($theQuery) {
                                while ($row = $theQuery->fetch_assoc()) {
                                    $old = explode(',', $row['RS_DATA']);
                                    $new = array_diff($old, $idsArr);
                                    RSQuery('UPDATE rs_property_multiIdentifiers SET RS_DATA = "' . implode(',', $new) . '" WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $row['RS_ITEMTYPE_ID'] . ' AND RS_ITEM_ID = ' . $row['RS_ITEM_ID'] . ' AND RS_PROPERTY_ID = ' . $row['RS_PROPERTY_ID']);
                                }
                            }
                        } else {
                            RSQuery('UPDATE rs_property_multiIdentifiers SET RS_DATA = "" WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id']);
                        }
                    }
                }
            }
        }
    }

    // delete the user associations, if any
    if ($itemTypeID == getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID)) {
        if ($ids != '') {
            RSQuery('UPDATE rs_users SET RS_ITEM_ID = 0 WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEM_ID IN (' . $ids . ')');
        } else {
            RSQuery('UPDATE rs_users SET RS_ITEM_ID = 0 WHERE RS_CLIENT_ID = ' . $clientID);
        }
    }
}

function _dbInsertItemPropertyValue($itemTypeID, $itemID, $propertyID, $propertyType, $propertyValue, $clientID) {
    global $propertiesTables, $auditTrailPropertiesTables, $RSuserID, $RStoken;

    if (($propertyType == "identifiers") && ($propertyValue == "")) {
        // Avoid the insertion of empty values as identifiers in a multi identifier property
        $propertyValue = "0";
    }
    if ($propertyType == 'identifiers') {
        //mmulti identifier property, generate order with same number of values
        $propertyValue = "'" . $propertyValue . "'";
        $propertyOrder = "'" . implode(',', array_fill(0, count(explode(',',$propertyValue)), '0')) . "'";

        // Launch the insert in the propertyTables
        $result = RSQuery('INSERT INTO ' . $propertiesTables[$propertyType] . ' ' . '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID, RS_ORDER) ' . 'VALUES ' . '(' . $itemTypeID . ',' . $itemID . ',' . $propertyValue . ',' . $propertyID . ',' . $clientID . ',' . $propertyOrder . ')');

    } elseif (($propertyType != 'image') && ($propertyType != 'file')) {
        $propertyValue = "'" . $propertyValue . "'";

        // Launch the insert in the propertyTables
        $result = RSQuery('INSERT INTO ' . $propertiesTables[$propertyType] . ' ' . '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID) ' . 'VALUES ' . '(' . $itemTypeID . ',' . $itemID . ',' . $propertyValue . ',' . $propertyID . ',' . $clientID . ')');

    } else {
        // We are storing an image or a file
        // The name of the file comes separated from the data by character ":"
        $pieces = explode(":", $propertyValue);

        if (count($pieces) == 1) {
            // No name provided, so store an empty name
            $propertyName = "";
        } else {
            $propertyName = $pieces[0];
            $propertyValue = $pieces[1];
        }

        // If the 0x character is not present we add it. It must be used for the query
        if ((substr($propertyValue, 0, 2) != '0x') && ($propertyValue != ''))
            $propertyValue = '0x' . $propertyValue;

        // If the value is still empty, enclose it in quotes in order to form a valid query
        if ($propertyValue == '') {
            $propertyValue = "''";
            $propertySize = 0;
        } else {
            // As the value is codified in hex characters, it takes double the space to represent the file
            // Therefore we must divide the length by 2 in order to get the right length.
            // We subtract 2 because the 0x character included
            $propertySize = (strlen($propertyValue) - 2) / 2;
        }

        // Launch the insert in the propertyTables
        $result = RSQuery('INSERT INTO ' . $propertiesTables[$propertyType] . ' ' .
        '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_NAME, RS_SIZE, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID) ' .
        'VALUES ' . '(' . $itemTypeID . ',' . $itemID . ',\'' . $propertyName . '\',' . $propertySize . ',' . $propertyValue . ',' . $propertyID . ',' . $clientID . ')');
    }

    if ($result == 1) {
        // If the insertion in the property tables is correct, launch the insertion in the audit trail if necessary
        // check if property is Audit Trail
        $response = checkForAuditTrail($propertyID, $clientID);

        if ($response && $response['auditTrail'] == 1) {
            //avoid empty user id
            if (!isset($RSuserID)||$RSuserID == "") $RSuserID = 0;

            //Insert in the audit trail tables depending the itemTypeID
            $theQuery = 'INSERT INTO ' . $auditTrailPropertiesTables[$propertyType] .
            ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_CLIENT_ID, RS_USER_ID, RS_TOKEN,    RS_CHANGED_DATE, RS_FINAL_VALUE) VALUES (' .
            $itemTypeID . ',' . $itemID . ',' . $propertyID . ',' . $clientID . ',' . $RSuserID . ',"' . $RStoken . '","' . date("Y-m-d H:i:s") . '",' . $propertyValue . ')';

            $queryResult = RSQuery($theQuery);
            return $queryResult;
        } else {
            //Nothing to do. Return true
            return $result;
        }
    } else {
        return $result;
    }
}

// Delete a client item property value defined
function deleteItemPropertyValue($itemTypeID, $itemID, $propertyID, $clientID, $propertyType = '') {
    global $propertiesTables;

    if ($propertyType == '') {
        $propertyType = getClientPropertyType($propertyID, $clientID);
    }

    if(RSQuery("DELETE FROM " . $propertiesTables[$propertyType] . " WHERE RS_ITEMTYPE_ID = " . $itemTypeID . " AND RS_ITEM_ID = " . $itemID . " AND RS_PROPERTY_ID = " . $propertyID . " AND RS_CLIENT_ID = " . $clientID) && ($propertyType == 'image' || $propertyType == 'file')){
        deleteMediaFile($clientID,$itemID,$propertyID);
    }

    // We add the new item ID to the array of created itemIDs
    global $RSMupdatedItemIDs;
    if (!in_array($itemTypeID . "," . $itemID, $RSMupdatedItemIDs)) $RSMupdatedItemIDs[] = $itemTypeID . "," . $itemID;
}

// Return the value of the property passed for all items
function getItemsPropertyValues($propertyID, $clientID, $itemIDs = '', $propertyType = '', $itemTypeID = '', $translateIds = false, $returnOrder = 0, &$orderArray = array()) {
    global $propertiesTables;

    // If the itemTypeID was not passed... retrieve it
    if ($itemTypeID == '') $itemTypeID = getClientPropertyItemType($propertyID, $clientID);

    // If the property type was not passed... retrieve it
    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);

    if ($propertyType == 'image' || $propertyType == 'file') {
        $theQuery = 'SELECT RS_ITEM_ID AS "ID", CONCAT(RS_NAME,":",CAST(RS_SIZE AS CHAR)) AS "DATA" FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_PROPERTY_ID = ' . $propertyID;
    } else {
        $positionSelect = "";
        if ($returnOrder && (isSingleIdentifier($propertyType) || isMultiIdentifier($propertyType))) {
            $positionSelect = ', RS_ORDER AS "DATA_ord"';
        }
        $theQuery = 'SELECT RS_ITEM_ID AS "ID", ' . convertData('RS_DATA', $propertyType) . ' AS "DATA"' . $positionSelect . ' FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_PROPERTY_ID = ' . $propertyID;
    }

    if ($itemIDs!= '') {
        $theQuery .= ' AND RS_ITEM_ID IN (' . $itemIDs . ')';
    }

    $theQuery .= ')';

    $result = RSQuery($theQuery);

    if ($result) {
        // query OK
        $properties = array();

        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }

        if ($translateIds && isIdentifier($propertyID, $clientID, $propertyType)) $properties = _translateIds($properties, array(array('ID' => $propertyID, 'type' => $propertyType, 'name' => 'DATA')), $clientID);

        $results = array();
        foreach($properties as $property) {
            $results[$property['ID']] = $property['DATA'];
            if (isset($property['DATA_ord'])) {
                $orderArray[$property['ID']] = $property['DATA_ord'];
            }
        }

        return $results;
    } else {
        // query NOK
        return false;
    }
}

// Return the value of the property passed
function getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType = '', $itemTypeID = '') {
    global $propertiesTables;

    // If the itemTypeID was not passed... retrieve it
    if ($itemTypeID == '') $itemTypeID = getClientPropertyItemType($propertyID, $clientID);
    if ($itemTypeID == '0') return;

    // If the property type was not passed... retrieve it
    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);
    if ($propertyType == '') return;

    if ($propertyType == 'image' || $propertyType == 'file') {
        $result = RSQuery('SELECT CONCAT(RS_NAME,":",CAST(RS_SIZE AS CHAR)) AS "DATA" FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID = ' . $itemID . ' AND RS_PROPERTY_ID = ' . $propertyID . ')');
    } else
        $result = RSQuery('SELECT ' . convertData('RS_DATA', $propertyType) . ' AS "DATA" FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID = ' . $itemID . ' AND RS_PROPERTY_ID = ' . $propertyID . ')');

    if ($result && $propertyValue = $result->fetch_assoc()) return $propertyValue['DATA'];

    return;

}

// Return the value of the property passed
function getItemDataPropertyValue($itemID, $propertyID, $clientID, $propertyType = '', $itemTypeID = '') {
    global $propertiesTables;
    global $enable_image_cache;
    global $enable_file_cache;
    global $RSimageCache;
    global $RSfileCache;

    // If the itemTypeID was not passed... retrieve it
    if ($itemTypeID == '') $itemTypeID = getClientPropertyItemType($propertyID, $clientID);
    if ($itemTypeID == '0') return;

    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);
    if ($propertyType == '' || !array_key_exists($propertyType,$propertiesTables)) return;
    // property type not passed... retrieve it

    if ($propertyType == 'image' || $propertyType == 'file') {
        // Check if file/image is in cache
        $enable_cache = false;
        if ($propertyType == 'image') {
            $directory = $RSimageCache . "/" . $clientID . "/" . $propertyID . "/";
            $file_name = "img_" . $itemID;
            $enable_cache = $enable_image_cache;
        } else {
            $directory = $RSfileCache . "/" . $clientID . "/" . $propertyID . "/";
            $file_name = "file_" . $itemID;
            $enable_cache = $enable_file_cache;
        }
        $file_path = $directory . $file_name;

        //check file in cache
        $nombres_archivo = glob($file_path . "_*");

        if ($propertyType == 'image') {
            //Check if cached images are resized versions of original file with format like img_84_250_320_h_Rm90byBQZXJmaWwuanBn.jpg
            for ($i=count($nombres_archivo)-1;$i>=0;$i--) {
                if (preg_match("/img_\d+_.*?_.*?_/i",$nombres_archivo[$i])) unset($nombres_archivo[$i]);
            }
            $nombres_archivo = array_values($nombres_archivo);
        }

        if ($enable_cache && count($nombres_archivo) > 0) {
            // The file exists in cache, return cached file
            return bin2hex(file_get_contents($nombres_archivo[0]));

        } else {
            $result = RSQuery('SELECT RS_DATA AS "DATA", RS_SIZE AS "SIZE", RS_NAME AS "NAME"  FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID = ' . $itemID . ' AND RS_PROPERTY_ID = ' . $propertyID . ')');

            if ($result && $propertyValue = $result->fetch_assoc()) {
                // Check if we need to recover file data from media server
                $data = $propertyValue['DATA'];

                // If file data is empty but the size field is > 0 then the file is in media server
                if ($propertyValue['SIZE'] > 0 && $data == '') {
                    $fileData = getMediaFile($clientID,$itemID,$propertyID);
                    $data = $fileData['RS_DATA'];
                }

                // Save file/image in cache
                if ($enable_cache) saveFileCache($data, $file_path, $propertyValue['NAME'], pathinfo($propertyValue['NAME'], PATHINFO_EXTENSION));

                return bin2hex($data);
            }
        }

    } else {
        $result = RSQuery('SELECT ' . convertData('RS_DATA', $propertyType) . ' AS "DATA" FROM ' . $propertiesTables[$propertyType] . ' WHERE (RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID = ' . $itemID . ' AND RS_PROPERTY_ID = ' . $propertyID . ')');

        if ($result && $propertyValue = $result->fetch_assoc()) {
            return $propertyValue['DATA'];
        }

    }

    return;
}

// Return the audit trail for a given item and property
function getAuditTrail($clientID, $propertyID, $itemID) {
        global $auditTrailPropertiesTables;

        // Obtein the itemType pertaining to the passed property
        $itemTypeID = getItemTypeIDFromProperties(array($propertyID), $clientID);

        //get the type of the property
        $propertyType = getPropertyType($propertyID,$clientID);

        $table = $auditTrailPropertiesTables[$propertyType];

        //Build the query by type
        $theQuery = "SELECT " .
                                                    "rs_users.RS_LOGIN                                    AS 'userName',"         .
                                    $table.".RS_TOKEN                                                     AS 'token',"                .
                                    $table.".RS_DESCRIPTION                                         AS 'description',"    .
                                    $table.".RS_CHANGED_DATE                                        AS 'changedDate',"    .
            convertData($table.".RS_INITIAL_VALUE",$propertyType)." AS 'initialValue'," .
            convertData($table.".RS_FINAL_VALUE"    ,$propertyType)." AS 'finalValue' "     .
            "FROM "    . $table . " LEFT JOIN rs_users ".
            "ON ".
                     "rs_users.RS_CLIENT_ID = " . $table . ".RS_CLIENT_ID " .
            " AND rs_users.RS_USER_ID = "     . $table . ".RS_USER_ID "     .
            "WHERE " .
                                $table . ".RS_ITEMTYPE_ID=" . $itemTypeID .
            " AND " . $table . ".RS_ITEM_ID="         . $itemID         .
            " AND " . $table . ".RS_PROPERTY_ID=" . $propertyID .
            " AND " . $table . ".RS_CLIENT_ID="     . $clientID     .
            " ORDER BY " . $table . ".RS_CHANGED_DATE ASC";

        // execute query
        $theHistory = RSQuery($theQuery);
    if ($theHistory) {
            // get history values
            while ($row = $theHistory->fetch_assoc()) {
                    // store info
                    $results[] = array(
                            'propertyId'   => $propertyID,
                            'propertyType' => $propertyType,
                            'itemID'       => $itemID,
                            'clientID'     => $clientID,
                            'userName'     => ($row['userName']!=""?$row['userName']:$row['token']),
                            'description'  => $row['description' ],
                            'changedDate'  => $row['changedDate' ],
                            'initialValue' => $row['initialValue'],
                            'finalValue'   => $row['finalValue'    ]
                    );
            }
    }

        return ($results);
}

// Return an array of recursive identifiers from the property passed
// This function only works with identifier and identifiers item types
function getRecursiveIdentifiersList($itemTypeID, $itemID, $propertyID, $clientID, &$arrayFinds, $propertyType = '', $referredItemTypeID = '') {
    // Check if the passed property is a identifier
    if ($propertyType == '')
        $propertyType = getPropertyType($propertyID, $clientID);

    // If the property is not an identifier, exit
    if ($propertyType != 'identifier' && $propertyType != 'identifiers')
        return false;

    // Check if the property is recursive
    // A recursive property points to the same item type
    if ($referredItemTypeID == '')
        $referredItemTypeID = getClientPropertyReferredItemType($propertyID, $clientID);

    // If it is not recursive, then exit
    if ($referredItemTypeID != $itemTypeID)
        return false;

    // Get the property value for the current item
    $result = getItemPropertyValue($itemID, $propertyID, $clientID, $propertyType);

    $newIDsArr = explode(",", $result);

    // Ten loop over the identifiers and get their items recursively
    foreach ($newIDsArr as $newID) {
        if (!in_array($newID, $arrayFinds) & ($newID != "")) {
            // The element is not already in the array, so add it and continue looping
            $arrayFinds[] = $newID;
            if (!getRecursiveIdentifiersList($itemTypeID, $newID, $propertyID, $clientID, $arrayFinds, $propertyType, $referredItemTypeID))
                return false;
        }
    }

    return true;
}

// Set item property value
// TODO --> $appPropertyName is the name of the application property associated with the client item property; $itemID is the ID of the client item
function setItemPropertyValue($appPropertyName, $itemTypeID, $itemID, $clientID, $newData, $userID = 0) {

    // get client property info
    $propertyID = getClientPropertyID_RelatedWith_byName($appPropertyName, $clientID);
    if ($propertyID != '0') {
        // property related
        return setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $newData, '', $userID);
    } else {
        // property not related
        return false;
    }
}

// Return the main property value of an item
function getClientItemMainPropertyValue($itemID, $itemTypeID, $clientID) {
    return getItemPropertyValue($itemID, getMainPropertyID($itemTypeID, $clientID), $clientID);
}

// Return the filter clause
function _getFilterClause($property) {
    switch ($property['mode']) {
        case 'IN' :
            // the property value must be inside the data set
            $filterClause = 'FIND_IN_SET("' . $property['value'] . '",filter' . $property['ID'] . '.RS_DATA) > 0';
            break;
        case '<-IN' :
            // the data must be inside the property value set
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA IN (' . $property['value'] . ')';
            break;
        case '=' :
            // the data value must be = the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA = "' . $property['value'] . '"';
            break;
        case '>' :
            // the data must be greater than the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA > "' . $property['value'] . '"';
                        break;
        case '<' :
            // the data must be lowest than the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA < "' . $property['value'] . '"';
                        break;
        case '>=' :
            // the data must be greater than the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA >= "' . $property['value'] . '"';
            break;
        case '<=' :
            // the data must be lowest than the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA <= "' . $property['value'] . '"';
            break;
        case '<>' :
            // the data must be different by the property value
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA <> "' . $property['value'] . '"';
            break;
        case 'SAME_OR_BEFORE' :
            // date filter: the data (a date), must be same or before than the property date value
            $filterClause = 'DATEDIFF(filter' . $property['ID'] . '.RS_DATA,"' . $property['value'] . '") <= 0';
            break;
        case 'SAME_OR_AFTER' :
            // date filter: the data (a date), must be same or after than the property date value
            $filterClause = 'DATEDIFF(filter' . $property['ID'] . '.RS_DATA,"' . $property['value'] . '") >= 0';
            break;
        case 'BEFORE' :
            // date filter: the data (a date), must be before than the property date value
            $filterClause = 'DATEDIFF(filter' . $property['ID'] . '.RS_DATA,"' . $property['value'] . '") < 0';
            break;
        case 'AFTER' :
            // date filter: the data (a date), must be after than the property date value
            $filterClause = 'DATEDIFF(filter' . $property['ID'] . '.RS_DATA,"' . $property['value'] . '") > 0';
            break;
        case 'TIME_SAME_OR_BEFORE' :
            // time filter: the data (a datetime), must be same or before than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",filter' . $property['ID'] . '.RS_DATA) <= 0';
            break;
        case 'TIME_SAME_OR_AFTER' :
            // time filter: the data (a datetime), must be same or after than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",filter' . $property['ID'] . '.RS_DATA) >= 0';
            break;
        case 'TIME_BEFORE' :
            // time filter: the data (a datetime), must be before than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",filter' . $property['ID'] . '.RS_DATA) < 0';
            break;
        case 'TIME_AFTER' :
            // time filter: the data (a datetime), must be after than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",filter' . $property['ID'] . '.RS_DATA) > 0';
            break;
        case 'LIKE' :
            // the data must be LIKE the property value (see mySQL LIKE clause)
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA LIKE "%' . $property['value'] . '%"';
            break;
        case 'GT' :
            // the data must be greater than the property value (string compare)
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA > "' . $property['value'] . '"';
            break;
        case 'LT' :
            // the data must be lowest than the property value (string compare)
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA < "' . $property['value'] . '"';
            break;
        case 'GE' :
            // the data must be greater than the property value (string compare)
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA >= "' . $property['value'] . '"';
            break;
        case 'LE' :
            // the data must be lowest than the property value (string compare)
            $filterClause = 'filter' . $property['ID'] . '.RS_DATA <= "' . $property['value'] . '"';
            break;
    }

    return $filterClause;
}

// Return the filter clause
function _getTranslatedFilterClause($property, $filterPropertyType, $filterCount, $clientID) {

    $translatedFilterSubquery = _getTranslatedFilterSubquery($property, $filterPropertyType, $filterCount, $clientID);

    switch ($property['mode']) {
        case 'IN' :
            // the property value must be inside the data set
            $filterClause = '"' . $property['value'] . '" IN ' . $translatedFilterSubquery;
            break;
        case '<-IN' :
            // the data must be inside the property value set
            $filterClause = $translatedFilterSubquery . ' IN (' . $property['value'] . ')';
            break;
        case '=' :
            // the data value must be = the property value
            $filterClause = '"' . $property['value'] . '" = ALL ' . $translatedFilterSubquery;
            break;
        case '>' :
            // the data must be greater than the property value
            $filterClause = $property['value'] . ' < ALL ' . $translatedFilterSubquery;
            break;
        case '<' :
            // the data must be lowest than the property value
            $filterClause = $property['value'] . ' > ALL ' . $translatedFilterSubquery;
            break;
        case '>=' :
            // the data must be greater than the property value
            $filterClause = $property['value'] . ' <= ALL ' . $translatedFilterSubquery;
            break;
        case '<=' :
            // the data must be lowest than the property value
            $filterClause = $property['value'] . ' >= ALL ' . $translatedFilterSubquery;
            break;
        case '<>' :
            // the data must be different by the property value
            $filterClause = '"' . $property['value'] . '" <> ALL ' . $translatedFilterSubquery;
            break;
        case 'SAME_OR_BEFORE' :
            // date filter: the data (a date), must be same or before than the property date value
            $filterClause = 'DATEDIFF(' . $translatedFilterSubquery . ',"' . $property['value'] . '") <= 0';
            break;
        case 'SAME_OR_AFTER' :
            // date filter: the data (a date), must be same or after than the property date value
            $filterClause = 'DATEDIFF(' . $translatedFilterSubquery . ',"' . $property['value'] . '") >= 0';
            break;
        case 'BEFORE' :
            // date filter: the data (a date), must be before than the property date value
            $filterClause = 'DATEDIFF(' . $translatedFilterSubquery . ',"' . $property['value'] . '") < 0';
            break;
        case 'AFTER' :
            // date filter: the data (a date), must be after than the property date value
            $filterClause = 'DATEDIFF(' . $translatedFilterSubquery . ',"' . $property['value'] . '") > 0';
            break;
        case 'TIME_SAME_OR_BEFORE' :
            // time filter: the data (a datetime), must be same or before than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",' . $translatedFilterSubquery . ') <= 0';
            break;
        case 'TIME_SAME_OR_AFTER' :
            // time filter: the data (a datetime), must be same or after than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",' . $translatedFilterSubquery . ') >= 0';
            break;
        case 'TIME_BEFORE' :
            // time filter: the data (a datetime), must be before than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",' . $translatedFilterSubquery . ') < 0';
            break;
        case 'TIME_AFTER' :
            // time filter: the data (a datetime), must be after than the property datetime value
            $filterClause = 'TIMESTAMPDIFF(SECOND,"' . $property['value'] . '",' . $translatedFilterSubquery . ') > 0';
            break;
        case 'LIKE' :
            // the data must be LIKE the property value (see mySQL LIKE clause)
            $filterClause = $translatedFilterSubquery . ' LIKE "' . $property['value'] . '"';
            break;
        case 'GT' :
            // the data must be greater than the property value (string compare)
            $filterClause = '"' . $property['value'] . '" < ALL ' . $translatedFilterSubquery;
            break;
        case 'LT' :
            // the data must be lowest than the property value (string compare)
            $filterClause = '"' . $property['value'] . '" > ALL ' . $translatedFilterSubquery;
            break;
        case 'GE' :
            // the data must be greater than the property value (string compare)
            $filterClause = '"' . $property['value'] . '" <= ALL ' . $translatedFilterSubquery;
            break;
        case 'LE' :
            // the data must be lowest than the property value (string compare)
            $filterClause = '"' . $property['value'] . '" >= ALL ' . $translatedFilterSubquery;
            break;
    }

    return $filterClause;
}

// Takes a items-properties table and translate the identifiers
function _getTranslatedFilterSubquery($property, $filterPropertyType, $filterCount, $clientID) {
    global $propertiesTables;

    if (($filterPropertyType == 'identifier') || ($filterPropertyType == 'identifiers')) {
        // get identifier property referred item type
        $referredItemTypeID = getClientPropertyReferredItemType($property['ID'], $clientID);
        $mainPropertyID = getMainPropertyID($referredItemTypeID, $clientID);
        $subQuery = "(SELECT RS_DATA FROM " . $propertiesTables[getPropertyType($mainPropertyID, $clientID)] . " subFilter" . $filterCount . " WHERE subFilter" . $filterCount . ".RS_ITEM_ID IN (filter" . $property['ID'] . ".RS_DATA) AND subFilter" . $filterCount . ".RS_PROPERTY_ID=" . $mainPropertyID . " AND subFilter" . $filterCount . ".RS_ITEMTYPE_ID=" . $referredItemTypeID . " AND subFilter" . $filterCount . ".RS_CLIENT_ID=" . $clientID . ")";
    } else {
        //TO DO identifier2itemtype, identifier2property
        $subQuery = 'filter' . $property['ID'] . '.RS_DATA';
    }

    return $subQuery;
}

// Takes a items-properties table and translate the identifiers
function _translateIds($results, $propertiesToTranslate, $clientID) {
    foreach ($propertiesToTranslate as $property) {

        // prepare the identifier's list to translate
        $ids = array();
                $values = array();

        if (($property['type'] == 'identifier') || ($property['type'] == 'identifier2itemtype') || ($property['type'] == 'identifier2property')) {
            // This property is a single identifier
            foreach ($results as $row) $ids[$row[$property['name']]] = '';

        } elseif ($property['type'] == 'identifiers') {
            // This property is a multiple identifier so we must translate several values
            for ($i = 0; $i < count($results); $i++) {
                $values[$i] = explode(',', $results[$i][$property['name']]);
                foreach ($values[$i] as $value) $ids[$value] = '';
            }
        }

        // remove the zero values (they can't be translated)
        // at the beginning of RSM, zero values were created for empty properties
        // now they should not exist for new properties. We remove the zeroes just because maybe there are legacy values.
        unset($ids['0'], $ids['']);

        $idsList = '';
        foreach ($ids as $key => $value) $idsList .= $key . ',';

        $ids['0'] = '';
        $ids[''] = '';

        if (($property['type'] == 'identifier') || ($property['type'] == 'identifiers')) {

            // get identifier property referred item type
            $referredItemTypeID = getClientPropertyReferredItemType($property['ID'], $clientID);

            // get the mainvalues for the translation
            $items = IQ_getItems($referredItemTypeID, $clientID, false, trim($idsList, ','));

            if ($items && $items->num_rows > 0)
                while ($item = $items->fetch_assoc())
                    $ids[$item['ID']] = $item['mainValue'];

        } elseif ($property['type'] == 'identifier2itemtype') {
            $items = getClientItemTypes($clientID, trim($idsList, ','));
            foreach ($items as $item)
                $ids[$item['ID']] = $item['name'];

        } elseif ($property['type'] == 'identifier2property') {
            $items = getProperties_byIDs(trim($idsList, ','), $clientID, 'RS_NAME AS "name"');
            if ($items) {
                foreach ($items as $item)
                    $ids[$item['RS_PROPERTY_ID']] = $item['name'];
            }
        }

        // finally insert the translated values in the results array

        if (($property['type'] == 'identifier') || ($property['type'] == 'identifier2itemtype') || ($property['type'] == 'identifier2property')) {

            if (isset($property['trName'])) {
                for ($i = 0; $i < count($results); $i++) {
                    $result = $results[$i];
                    $result[$property['trName']] = $ids[$result[$property['name']]];
                    $results[$i] = $result;
                }

            } else
                for ($i = 0; $i < count($results); $i++) {
                    $result = $results[$i];
                    $result[$property['name']] = $ids[$result[$property['name']]];
                    $results[$i] = $result;
                }

        } else {

            if (isset($property['trName'])) {
                for ($i = 0; $i < count($results); $i++) {
                    for ($k = 0; $k < count($values[$i]); $k++)
                        $values[$i][$k] = $ids[$values[$i][$k]];
                    $result = $results[$i];
                    $result[$property['trName']] = implode('; ', $values[$i]);
                    $results[$i] = $result;
                }
            } else {
                for ($i = 0; $i < count($results); $i++) {
                    for ($k = 0; $k < count($values[$i]); $k++)
                        $values[$i][$k] = $ids[$values[$i][$k]];
                    $result = $results[$i];
                    $result[$property['name']] = implode('; ', $values[$i]);
                    $results[$i] = $result;
                }
            }
        }
    }

    return $results;
}

// ********************************************************************** //
// ************** APP AND CLIENT ITEM TYPES RELATIONSHIPS *************** //
// ********************************************************************** //

// --------------------- create, update, delete -------------------------

// Create new relationship between an application item type and a client item type;
function createItemTypeRelationship($clientItemTypeID, $appItemTypeID, $clientID) {
    // delete previous relationship of the client item type if occurs
    deleteClientItemTypeRelationship($clientItemTypeID, $clientID);
    // delete previous relationship of the application item type if occurs
    deleteAppItemTypeRelationship($appItemTypeID, $clientID);
    // create new relationship
    RSQuery("INSERT INTO rs_item_type_app_relations (RS_ITEMTYPE_ID, RS_CLIENT_ID, RS_ITEMTYPE_APP_ID, RS_MODIFIED_DATE) VALUES (" . $clientItemTypeID . "," . $clientID . "," . $appItemTypeID . ",NOW())");
}

// Delete old client item type relationship
function deleteClientItemTypeRelationship($clientItemTypeID, $clientID) {
    RSQuery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID);
}

// Delete old application item type relationship
function deleteAppItemTypeRelationship($appItemTypeID, $clientID) {
    RSQuery("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = " . $appItemTypeID . " AND RS_CLIENT_ID = " . $clientID);
}

// ------------------------------ get -----------------------------------

// Return the ID of the application item type related with the client item type passed
function getAppItemTypeID_RelatedWith($clientItemTypeID, $clientID) {

    $result = RSQuery("SELECT RS_ITEMTYPE_APP_ID FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID);

    if (!$result) return '0';

    $appItemTypeID = $result->fetch_assoc();

    if ($appItemTypeID) {
        return $appItemTypeID['RS_ITEMTYPE_APP_ID'];
    } else
        return '0';
}

// Return the name of the application item type related with the client item type passed
function getAppItemTypeName_RelatedWith($clientItemTypeID, $clientID) {

    $appItemTypeIDRelated = getAppItemTypeID_RelatedWith($clientItemTypeID, $clientID);

    return getAppItemTypeName($appItemTypeIDRelated);
}

// Return the ID of the client item type related with the application item type passed
function getClientItemTypeID_RelatedWith($appItemTypeID, $clientID) {

    $result = RSQuery("SELECT RS_ITEMTYPE_ID FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = " . $appItemTypeID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $clientItemTypeID = $result->fetch_assoc()) {
        return $clientItemTypeID['RS_ITEMTYPE_ID'];
    } else
        return '0';
}

// Return the ID of the client item type related with the application item type passed (by Name)
function getClientItemTypeID_RelatedWith_byName($appItemTypeName, $clientID) {
    // build query
    $theQuery = 'SELECT a.RS_ITEMTYPE_ID AS "ID" FROM rs_item_type_app_relations a INNER JOIN rs_item_type_app_definitions b ON (a.RS_ITEMTYPE_APP_ID = b.RS_ID) WHERE (a.RS_CLIENT_ID = ' . $clientID . ') AND (b.RS_NAME = "' . $appItemTypeName . '")';

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $itemType = $result->fetch_assoc())
        return $itemType['ID'];

    return 0;
}

// Return the ID of the client item type related with the application item type passed (by Name)
function getClientItemTypeIDs_RelatedWith_byName($appItemTypeNames, $clientID) {
    $theIDs = array();

    foreach ($appItemTypeNames as $appItemTypeName)
        $theIDs[] = getClientItemTypeID_RelatedWith_byName($appItemTypeName, $clientID);

    return $theIDs;
}

// Return the name of the client item type related with the application item type passed
function getClientItemTypeName_RelatedWith($appItemTypeID, $clientID) {
    $clientItemTypeIDRelated = getClientItemTypeID_RelatedWith($appItemTypeID, $clientID);

    return getClientItemTypeName($clientItemTypeIDRelated, $clientID);
}

// Return the main property ID of the client item type related with the application item type passed
function getClientItemTypeMainProperty_RelatedWith($appItemTypeID, $clientID) {
    $clientItemTypeIDRelated = getClientItemTypeID_RelatedWith($appItemTypeID, $clientID);

    return getClientItemTypeMainProperty($clientItemTypeIDRelated, $clientID);
}

// Return the list of related properties of the item type passed
function getClientItemTypeProperties_Related($clientItemTypeID, $clientID) {
    $results = RSQuery("SELECT RS_PROPERTY_ID FROM rs_property_app_relations WHERE RS_PROPERTY_ID IN (SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_CATEGORY_ID IN (SELECT RS_CATEGORY_ID FROM rs_categories WHERE RS_ITEMTYPE_ID = " . $clientItemTypeID . " AND RS_CLIENT_ID = " . $clientID . ")) AND RS_CLIENT_ID = " . $clientID);

    $propertiesRelated = array();
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $propertiesRelated[] = $row['RS_PROPERTY_ID'];
        }
    }

    return $propertiesRelated;
}

// **********************************************************************
// ************** APP AND CLIENT PROPERTIES RELATIONSHIPS ***************
// **********************************************************************

// --------------------- create, update, delete -------------------------

// Create new relationship between an application item type and a client item type; if the client property and/or application property are already related, their relationships will be updated
function createPropertyRelationship($clientPropertyID, $appPropertyID, $clientID) {
    // delete previous relationship of the client property if occurs
    deleteClientPropertyRelationship($clientPropertyID, $clientID);
    // delete previous relationship of the application property if occurs
    deleteAppPropertyRelationship($appPropertyID, $clientID);
    // create new relationship
    RSQuery("INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES (" . $clientPropertyID . "," . $clientID . "," . $appPropertyID . ",NOW())");
}

// Delete old client property relationship
function deleteClientPropertyRelationship($clientPropertyID, $clientID) {
    RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = " . $clientPropertyID . " AND RS_CLIENT_ID = " . $clientID);
}

// Delete old application property relationship
function deleteAppPropertyRelationship($appPropertyID, $clientID) {
    RSQuery("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = " . $appPropertyID . " AND RS_CLIENT_ID = " . $clientID);
}

// ------------------------------ get -----------------------------------

// Return the ID of the application's property related with the client property passed
function getAppPropertyID_RelatedWith($clientPropertyID, $clientID) {

    $result = RSQuery("SELECT RS_PROPERTY_APP_ID FROM rs_property_app_relations WHERE RS_PROPERTY_ID = " . $clientPropertyID . " AND RS_CLIENT_ID = " . $clientID);
    if (!$result) return '0';

    $appPropertyID = $result->fetch_assoc();

    if ($appPropertyID)
        return $appPropertyID['RS_PROPERTY_APP_ID'];

    return '0';
}

// Return the name of the application's property related with the client property passed
function getAppPropertyName_RelatedWith($clientPropertyID, $clientID) {

    $appPropertyIDRelated = getAppPropertyID_RelatedWith($clientPropertyID, $clientID);

    if ($appPropertyIDRelated > 0)
        return getAppPropertyName($appPropertyIDRelated);

    return "";
}

// Return the item type of the application's property related with the client property passed
function getAppPropertyItemType_RelatedWith($clientPropertyID, $clientID) {

    $appPropertyIDRelated = getAppPropertyID_RelatedWith($clientPropertyID, $clientID);

    return getAppPropertyItemType($appPropertyIDRelated);
}

// Return the ID of the client property related with the application's property passed
function getClientPropertyID_RelatedWith($appPropertyID, $clientID) {

    $result = RSQuery("SELECT RS_PROPERTY_ID FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = " . $appPropertyID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $clientPropertyID = $result->fetch_assoc()) {
        return $clientPropertyID['RS_PROPERTY_ID'];
    } else {
        return '0';
    }
}

// Return the ID of the client property related with the application's property passed (by Name)
function getClientPropertyID_RelatedWith_byName($appPropertyName, $clientID) {

    // build query
    //$theQuery = 'SELECT c.RS_PROPERTY_ID AS "ID" FROM rs_property_app_definitions a INNER JOIN rs_property_app_relations b ON (a.RS_ID = b.RS_PROPERTY_APP_ID) INNER JOIN rs_item_properties c USING (RS_CLIENT_ID, RS_PROPERTY_ID) WHERE a.RS_NAME IN ("' . $sysName . '") AND b.RS_CLIENT_ID = ' . $clientID . ' AND c.RS_CLIENT_ID = ' . $clientID;
    $theQuery = 'SELECT a.RS_PROPERTY_ID AS "ID" FROM rs_property_app_relations a INNER JOIN rs_property_app_definitions b ON (a.RS_PROPERTY_APP_ID = b.RS_ID) WHERE (a.RS_CLIENT_ID = ' . $clientID . ') AND (b.RS_NAME = "' . $appPropertyName . '")';

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $property = $result->fetch_assoc()) {
        return $property['ID'];
    } else {
        return 0;
    }
}

// Return the name of the client property related with the application's property passed (by name)
function getClientPropertyName_RelatedWith_byName($appPropertyName, $clientID) {

    // build query
    $theQuery = 'SELECT rs_item_properties.RS_NAME AS "name" FROM rs_item_properties INNER JOIN rs_property_app_relations USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_property_app_definitions ON (rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) WHERE (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_property_app_definitions.RS_NAME = "' . $appPropertyName . '")';

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $property = $result->fetch_assoc()) {
        return $property['name'];
    } else {
        return '';
    }
}

// Return the name of the client property related with the application's property passed
function getClientPropertyName_RelatedWith($appPropertyID, $clientID) {

    // build query
    $theQuery = 'SELECT rs_item_properties.RS_NAME AS "name" FROM rs_item_properties INNER JOIN rs_property_app_relations USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_property_app_definitions ON (rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) WHERE (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_property_app_definitions.RS_ID = ' . $appPropertyID . ')';

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $property = $result->fetch_assoc()) {
        return $property['name'];
    } else {
        return '';
    }
}

// Return the ID of the category of the client property related with the application's property passed
function getClientPropertyCategory_RelatedWith($appPropertyID, $clientID) {

    $clientPropertyIDRelated = getClientPropertyID_RelatedWith($appPropertyID, $clientID);

    return getClientPropertyCategory($clientPropertyIDRelated, $clientID);
}

// Return the name of the category of the client property related with the application's property passed
function getClientPropertyCategoryName_RelatedWith($appPropertyID, $clientID) {

    $clientPropertyIDRelated = getClientPropertyID_RelatedWith($appPropertyID, $clientID);
    $categoryID = getClientPropertyCategory($clientPropertyIDRelated, $clientID);

    return getClientCategoryName($categoryID, $clientID);
}

// Return the item type ID of the client property related with the application's property passed
function getClientPropertyItemType_RelatedWith($appPropertyID, $clientID) {
    $clientPropertyIDRelated = getClientPropertyID_RelatedWith($appPropertyID, $clientID);
    return getClientPropertyItemType($clientPropertyIDRelated, $clientID);
}

// Return the item type ID of a group of propertiesID
function getItemTypeIDFromProperties($propertiesID, $clientID) {
    $itemTypeID = -1;

    foreach ($propertiesID as $propertyID) {
        // Ignore zero or null properties
        if (($propertyID == "") || ($propertyID == "0"))
            continue;

        $propertyID = ParsePID($propertyID, $clientID);
        // Ignore wrong or not related properties
        if ($propertyID == "0")
            continue;

        // Every property must belong to the same itemTypeID
        // Control change of $itemTypeID at every foreach loop
        $itemTypeID1 = getClientPropertyItemType($propertyID, $clientID);
        if (($itemTypeID1 == $itemTypeID) || ($itemTypeID == -1)) {
            $itemTypeID = $itemTypeID1;
        } else {
            $itemTypeID = 0;
            break;
        }
    }

    return $itemTypeID;
}

// Return the item type name of the client property related with the application's property passed
function getClientPropertyItemTypeName_RelatedWith($appPropertyID, $clientID) {

    $clientPropertyIDRelated = getClientPropertyID_RelatedWith($appPropertyID, $clientID);
    $itemTypeID = getClientPropertyItemType($clientPropertyIDRelated, $clientID);

    return getClientItemTypeName($itemTypeID, $clientID);
}

//***************************************************
// items queries.php
// The IQ functions should only be called from the RSM core and not from the outside
//***************************************************

// get items IDs (returns the query results directly)
function IQ_getItemIDs($itemtypeID, $clientID) {
    return RSQuery("SELECT RS_ITEM_ID AS 'ID' FROM rs_items WHERE RS_ITEMTYPE_ID = " . $itemtypeID . " AND RS_CLIENT_ID = " . $clientID);
}

// Get items with their main values (returns the query results directly)
function IQ_getItems($itemTypeID, $clientID, $sort = true, $ids = '', $limit = '') {
    global $propertiesTables;

    // get main property info
    $itemTypeID = parseITID($itemTypeID, $clientID);

        if ($itemTypeID == "") {
                return false;
        }

    $mainPropertyID     = getMainPropertyID ($itemTypeID, $clientID);
    $mainPropertyType = getPropertyType     ($mainPropertyID, $clientID);

    // check if ORDER clause is required
    $orderBy = "";
    if ($sort) {
        $orderBy = " ORDER BY mainProps.RS_DATA";
    }

    // check if IN clause is required
    $inClause = "";
    if ($ids != '') {
        $inClause = " AND items.RS_ITEM_ID IN (" . $ids . ")";
    }

    // check if limit clause is required
    $limitClause = "";
    if ($limit != '') {
            $limitClause = " LIMIT " . $limit;
    }

    return RSQuery("SELECT items.RS_ITEM_ID AS 'ID', " . convertData('mainProps.RS_DATA', $mainPropertyType) . " AS 'mainValue' " . "FROM rs_items items LEFT JOIN " . $propertiesTables[$mainPropertyType] . " mainProps " . "ON ( " . "mainProps.RS_PROPERTY_ID = " . $mainPropertyID . " AND " . "mainProps.RS_ITEMTYPE_ID = items.RS_ITEMTYPE_ID        AND " . "mainProps.RS_ITEM_ID         = items.RS_ITEM_ID                AND " . "mainProps.RS_CLIENT_ID     = items.RS_CLIENT_ID " . ") " . "WHERE (" . "items.RS_ITEMTYPE_ID = " . $itemTypeID . " " . "AND items.RS_CLIENT_ID = " . $clientID . $inClause . ")" . $orderBy . $limitClause);
}

// If possible, use the function getFilteredItemsIDs instead of this one.
// getFilteredItemsIDs is more complete and returns an array.
// **********************************************************************
// Return an array of filtered items (returns the query results directly);
// the parameter $filterProperties must be an array like (property 'ID', property 'value', [property 'mode']) representing the filter;
// the parameter $returnProperties must be an array like (property 'name', property 'ID') that will be use to obtain the values to return
function IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy = '', $limit = '', $ids = '', $filtersJoining = 'AND', &$propertiesToTranslate = array(), $returnOrder = 0) {
    global $propertiesTables;

    // If the filtersJoining is not specified, take "AND" by default
    if ($filtersJoining == "") {
        $filtersJoining = "AND";
    }

    // initialize the SELECT and FROM query parts strings
    $queryPartSELECT = "SELECT DISTINCT rs_items.RS_ITEM_ID AS 'ID'";
    if ($returnOrder) {
        $queryPartSELECT .= ", rs_items.RS_ORDER AS 'ITEM_ORDER'";
    }

    $queryPartFROM = "FROM rs_items";

    if ($ids != '') {
        $queryPartWHERE = "
            WHERE (rs_items.RS_ITEMTYPE_ID = " . $itemTypeID . "
                    AND rs_items.RS_CLIENT_ID = " . $clientID . "
                    AND rs_items.RS_ITEM_ID IN (" . $ids . "))";
    } else
        $queryPartWHERE = "
            WHERE (rs_items.RS_ITEMTYPE_ID = " . $itemTypeID . "
                    AND rs_items.RS_CLIENT_ID = " . $clientID . ")";

    // filter part
    $filterPropertyIds = array();
    $rejectedFilterPropertyIds = array();

    if (count($filterProperties) > 0) {
        //filter clauses that accept equal value
        $arrEquals = array('IN','<-IN','=','>=','<=','SAME_OR_BEFORE','SAME_OR_AFTER','TIME_SAME_OR_BEFORE','TIME_SAME_OR_AFTER','LIKE','GE','LE');

        foreach ($filterProperties as $property) {
            if ($property['ID'] == '0' || $property['ID'] == '')
                continue;

            //check if property already used for filtering (or rejected) and add it otherwise
            if (!array_key_exists($property['ID'],$filterPropertyIds) && !in_array($property['ID'],$rejectedFilterPropertyIds)) {
                //check property type
                $filterPropertyType = getPropertyType($property['ID'], $clientID);
                //get property default value
                $filterPropertyDefault = getClientPropertyDefaultValue($property['ID'], $clientID);

                //images and files can't be used as filter
                if ($filterPropertyType != "file" && $filterPropertyType != "image") {
                    // add property to array with value and filter property type
                    $filterPropertyIds[$property['ID']]=array("type" => $filterPropertyType, "default" => $filterPropertyDefault, "defaultCompare" => 0, "filters" => array());
                } else {
                    //add rejected property to list to avoid proccessing again
                    $rejectedFilterPropertyIds[]=$property['ID'];
                }
            }
            //add filter to grouped array if valid property
            if (!in_array($property['ID'],$rejectedFilterPropertyIds)) {
                $filterPropertyIds[$property['ID']]["filters"][]=$property;
                if ($property['value'] == $filterPropertyIds[$property['ID']]["default"] && (isset($property['mode']) && in_array($property['mode'],$arrEquals))) {
                    $filterPropertyIds[$property['ID']]["defaultCompare"]=1;
                }
            }
        }

        $subQueryCount=0;
        $filterPartWHERE = "";
        foreach ($filterPropertyIds as $propertyId=>$propertyValue) {
            //check if comparing with default value and in that case accept also when property doesn't exist
            if ($propertyValue["defaultCompare"] == 1) {
                //we must use left join
                $queryPartFROM .= " LEFT JOIN " . $propertiesTables[$propertyValue["type"]] . " filter" . $propertyId . " ON (rs_items.RS_ITEMTYPE_ID = filter" . $propertyId . ".RS_ITEMTYPE_ID AND rs_items.RS_ITEM_ID = filter" . $propertyId . ".RS_ITEM_ID AND rs_items.RS_CLIENT_ID = filter" . $propertyId . ".RS_CLIENT_ID AND filter" . $propertyId . ".RS_PROPERTY_ID = " . $propertyId . ")";
                $filterPartWHERE .= "(" ;

            } else {
                //only one inner join per propertyID
                $queryPartFROM .= " INNER JOIN " . $propertiesTables[$propertyValue["type"]] . " filter" . $propertyId . " ON (rs_items.RS_ITEMTYPE_ID = filter" . $propertyId . ".RS_ITEMTYPE_ID AND rs_items.RS_ITEM_ID = filter" . $propertyId . ".RS_ITEM_ID AND rs_items.RS_CLIENT_ID = filter" . $propertyId . ".RS_CLIENT_ID)";
                //add query where part
                $filterPartWHERE .= "(filter" . $propertyId . ".RS_PROPERTY_ID = " . $propertyId . " AND (" ;
            }

            if ($orderBy == $propertyId)
                $orderBy = 'filter' . $propertyId . '.RS_DATA';

            foreach ($propertyValue["filters"] as $filter) {
                $tmpFilter = "";

                if (!isset($filter['translate'])) {
                    if (isset($filter['mode'])) {
                        $tmpFilter =    _getFilterClause($filter);
                    } else {
                        $tmpFilter = "FIND_IN_SET('" . $filter['value'] . "', filter" . $propertyId . ".RS_DATA) > 0";
                    }
                } else {
                    $subQueryCount++;
                    if (isset($filter['mode'])) {
                        //TODO study if translate subquery can be reused for all filters from same property
                        $tmpFilter =    _getTranslatedFilterClause($filter, $propertyValue['type'], $subQueryCount, $clientID);
                    } else {
                        $tmpFilter = "'" . $filter['value'] . "' IN " . _getTranslatedFilterSubquery($filter, $propertyValue['type'], $subQueryCount, $clientID);
                    }
                }

                if (!(strtolower($filtersJoining) == "and" && ($filter['value'] != $propertyValue['default'] || (isset($filter['mode']) && !in_array($filter['mode'],$arrEquals))))) {
                    $tmpFilter = "(" . $tmpFilter . " OR filter" . $propertyId . ".RS_DATA IS NULL)";
                }

                $filterPartWHERE .= $tmpFilter . " " . $filtersJoining . " ";

            }

            $filterPartWHERE = substr($filterPartWHERE, 0, 0 - (strlen($filtersJoining) + 1)) . ")";

            if ($propertyValue["defaultCompare"] != 1) {
                $filterPartWHERE .= ")";
            }

            $filterPartWHERE .= " " . $filtersJoining . " ";
        }

        if ($filterPartWHERE != "") {
            $queryPartWHERE .= " AND ( " . substr($filterPartWHERE, 0, 0 - (strlen($filtersJoining) + 1)) . ")";
        }
    }

    // return values part TODO: dividir en queries mas sencillas para evitar el limite de 61 tablas en el inner join de MySQL
    $returnCount = 0;

    foreach ($returnProperties as $property) {
        if ($property['ID'] == 0) continue;
        // The property is invalid, so continue with the next property

        if (array_key_exists($property['ID'],$filterPropertyIds)) {
            //the return property is already used for filtering so we don't need another join
            // get return property type
            $returnPropertyType = $filterPropertyIds[$property['ID']]['type'];

            //Alias of the property table
            $tableAlias = "filter" . $property['ID'];

        } else {
            // get return property type
            $returnPropertyType = getPropertyType($property['ID'], $clientID);

            // continue building the query parts strings
            $returnCount++;

            //Alias of the property table
            $tableAlias = "returnValue" . $returnCount;

            //only in case we haven't used this property before add join for property table
            $queryPartFROM .= " LEFT JOIN " . $propertiesTables[$returnPropertyType] . " " . $tableAlias . " ON (rs_items.RS_ITEMTYPE_ID=" . $tableAlias . ".RS_ITEMTYPE_ID AND rs_items.RS_ITEM_ID=" . $tableAlias . ".RS_ITEM_ID AND rs_items.RS_CLIENT_ID=" . $tableAlias . ".RS_CLIENT_ID AND " . $tableAlias . ".RS_PROPERTY_ID = " . $property['ID'] . ")";
        }

        //images and files return only filename/size
        if ($returnPropertyType != "file" && $returnPropertyType != "image") {
            $positionSelect = "";
            if (isIdentifier($property['ID'], $clientID, $returnPropertyType)) {
                // add to the list
                $property['type'] = $returnPropertyType;
                $propertiesToTranslate[] = $property;
                if ($returnOrder && (isSingleIdentifier($returnPropertyType) || isMultiIdentifier($returnPropertyType))) {
                    $positionSelect = ', ' . $tableAlias . '.RS_ORDER AS "' . $property['name'] . '_ord"';
                }
            }

            $queryPartSELECT .= ', ' . convertData($tableAlias . '.RS_DATA', $returnPropertyType) . ' AS "' . $property['name'] . '"' . $positionSelect;

            if ($orderBy == $property['name'] || $orderBy == $property['ID'])
                $orderBy = $tableAlias . '.RS_DATA';

        } else {
            $queryPartSELECT .= ', CONCAT(' . $tableAlias . '.RS_NAME,":",' . $tableAlias . '.RS_SIZE) AS "' . $property['name'] . '"';
        }
    }

    // check if order by clause is required
    ($orderBy == '') ? $queryPartORDERBY = '' : $queryPartORDERBY = 'ORDER BY ' . $orderBy;

    // check if limit clause is required
    ($limit != '') ? $queryPartLIMIT = 'LIMIT ' . $limit : $queryPartLIMIT = '';

    return RSQuery($queryPartSELECT . " " . $queryPartFROM . " " . $queryPartWHERE . " " . $queryPartORDERBY . " " . $queryPartLIMIT);
}

// Return an array of filtered items;
// the parameter $filterProperties must be an array like (property 'ID', property 'value', [property 'mode']) representing the filter;
// the parameter $returnProperties must be an array like (property 'name', property 'ID', property 'trName') that will be use to obtain the values to return; the index 'trName' will contains the mainvalues translations for the identifiers properties; if not defined, the mainvalues will overwrite the ids
function getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy = '', $translateIds = false, $limit = '', $ids = '', $filtersJoining = 'AND', $returnOrder = 0, $allowFileResults = false, $extFilterRules = "", $decodeEntities = false) {
    $propertiesToTranslate = array();
    $optimizerValue = 1000;

    //prepare debug memory usage monitoring array
    //$lastValues=array('i'=>0,'startUsage'=>0,'startPeakUsage'=>0,'startAllocated'=>0,'startPeakallocated'=>0);
    //mem_increase_check($lastValues);

    // query the database
    $result = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy, $limit, $ids, $filtersJoining, $propertiesToTranslate, $returnOrder);

    //debug memory usage
    //mem_increase_check($lastValues);

    // build the results array
    if ($result) {
        //check if reached minimum results for fie use or not
        $results = false;
        if ($allowFileResults && $result->num_rows > $optimizerValue) {
            $results = mysqlToXML($result,$clientID,$itemTypeID,$translateIds?$propertiesToTranslate:array(),$extFilterRules,$decodeEntities);
        }

        // create results array if size < optimizerValue or file creation failed
        if (!$results) {
            if ($allowFileResults) {
                $results = new SplFixedArray($result->num_rows);
            } else {
                $results = array();
            }

            $i=0;
            while ($auxVarResult = $result->fetch_assoc()) {

                if ($decodeEntities) {
                    // Ensure UTF-8 compatibility
                        $aux_array = array();

                        foreach ($auxVarResult as $key => $res) {
                                $aux_array[html_entity_decode($key, ENT_COMPAT|ENT_QUOTES, "UTF-8")] = html_entity_decode($res, ENT_COMPAT|ENT_QUOTES, "UTF-8");
                        }

                    $auxVarResult = $aux_array;
                }

                $results[$i] = $auxVarResult;
                $i++;
            }
            if ($translateIds) $results = _translateIds($results, $propertiesToTranslate, $clientID);

            // Check for external rules & apply them if exist
            if ($extFilterRules != '') $results = applyExternalFilters($itemTypeID, $clientID, $results, $extFilterRules);
        }

        //debug memory usage
        //mem_increase_check($lastValues);

    } else {
        $results = array();
    }

    return $results;
}

//Function for removing accented chars
//TO DO check why not working
function Unaccent($string) {
    return preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
}

//    Receives a textFilter, clientID and userID.
//    Returns an array with the names and identifiers of the itemTypes where the textFilter appears, and the number of occurrences.
//    Only the allowed registers for the user are accounted.
function getItemTypeIDs_usingFilter($userID, $clientID, $textFilter) {
    $results = array();
    $ids = array();
    $filteredResults = array();

    //We need add a query for each item type/table
    $theQuery = "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_text         WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_longtext WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_text WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_floats     WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_integers     WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_dates        WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_datetime WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID;
    $theQuery = $theQuery . " GROUP BY RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID ORDER BY RS_ITEMTYPE_ID, RS_ITEM_ID";
/*
$theQuery = RSQuery("SELECT RS_MAIN_PROPERTY_ID FROM rs_item_types WHERE RS_ITEMTYPE_ID = " . $itemTypeID . " AND RS_CLIENT_ID = " . $clientID);

if ($theQuery && $mainProperty = $theQuery->fetch_assoc()) {
    return $mainProperty['RS_MAIN_PROPERTY_ID'];
} else {
    return '0';
}
}

function getPropertyType($propertyID, $clientID) {
$theQuery = RSQuery("SELECT RS_TYPE FROM rs_item_properties WHERE RS_PROPERTY_ID = " . parsePID($propertyID, $clientID) . " AND RS_CLIENT_ID = " . $clientID);

*/

    $result = RSquery($theQuery);

        if ($result) {
                while ($row = $result->fetch_assoc())
                        $results[] = array('RS_ITEMTYPE_ID' => $row['RS_ITEMTYPE_ID'], 'RS_PROPERTY_ID' => $row['RS_PROPERTY_ID'], 'RS_ITEM_ID' => $row['RS_ITEM_ID']);
            //$ids[] =
        }

/*
    $theQuery = $theQuery . "SELECT a.RS_ITEMTYPE_ID, a.RS_PROPERTY_ID, a.RS_ITEM_ID FROM rs_property_identifiers a INNER JOIN (rs_item_types b INNER JOIN c ON b.RS_ITEMTYPE_ID = c.RS_ITEMTYPE_ID AND b.RS_CLIENT_ID = c.RS_CLIENT_ID ) ON a.RS_ITEMTYPE_ID = b.RS_ITEMTYPE_ID AND a.RS_CLIENT_ID = b.RS_CLIENT_ID
     WHERE c.RS_DATA LIKE '%" . $textFilter . "%' AND a.RS_CLIENT_ID=" . $clientID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_ITEMTYPE_ID, RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_multiIdentifiers WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID;
*/

    //Filter allowed properties for the user
    $lastItemType = -1;
    $lastItemID = -1;
    $counter = 0;

    if (count($results) <> 0) {
        foreach ($results as $item) {
            //Control if the property is visible for the user
            if (isPropertyVisible($userID, $item['RS_PROPERTY_ID'], $clientID)) {
                //If the itemType has no changes or is the first register
                if ($item['RS_ITEMTYPE_ID'] == $lastItemType OR $lastItemType == -1) {
                    //If the itemID has changed but we are in the same itemType, we add +1 to the counter
                    if ($item['RS_ITEM_ID'] <> $lastItemID) {
                        $counter = $counter + 1;
                        $lastItemType = $item['RS_ITEMTYPE_ID'];
                        $lastItemID = $item['RS_ITEM_ID'];
                    }
                } else {
                    $filteredResults[] = array('ID' => $lastItemType, 'Name' => getClientItemTypeName($lastItemType, $clientID), 'NUM' => $counter);
                    $counter = 1;
                    $lastItemType = $item['RS_ITEMTYPE_ID'];
                    $lastItemID = $item['RS_ITEM_ID'];
                }
            }
        }
        $filteredResults[] = array('ID' => $lastItemType, 'Name' => getClientItemTypeName($lastItemType, $clientID), 'NUM' => $counter);
    }
    return $filteredResults;
}

//    The PHP receives an itemTypeID and a filterText (clientID and userID are needed too).
//    Returns an array of itemIDs and main properties where the filterText appears.
//    Only the allowed properties for the userID must be accounted.
function getItemIDs_RelatedWith_ItemID_usingFilter($clientID, $userID, $itemTypeID, $textFilter) {
    $results = array();
    $filteredResults = array();

    $theQuery = "SELECT RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_text         WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " AND RS_ITEMTYPE_ID=" . $itemTypeID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_longtext WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " AND RS_ITEMTYPE_ID=" . $itemTypeID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_floats     WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " AND RS_ITEMTYPE_ID=" . $itemTypeID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_dates        WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " AND RS_ITEMTYPE_ID=" . $itemTypeID . " UNION ";
    $theQuery = $theQuery . "SELECT RS_PROPERTY_ID, RS_ITEM_ID FROM rs_property_datetime WHERE RS_DATA LIKE '%" . $textFilter . "%' AND RS_CLIENT_ID=" . $clientID . " AND RS_ITEMTYPE_ID=" . $itemTypeID;
    $theQuery = $theQuery . " GROUP BY RS_PROPERTY_ID, RS_ITEM_ID ORDER BY RS_ITEM_ID";

    $result = RSquery($theQuery);

        if ($result) {
                while ($row = $result->fetch_assoc()) {
                        $results[] = array('RS_PROPERTY_ID' => $row['RS_PROPERTY_ID'], 'RS_ITEM_ID' => $row['RS_ITEM_ID']);
                }
        }

    //Filter allowed properties for the user
    $lastItemType = -1;
    $counter = 0;
    $mainProperty = getMainPropertyID($itemTypeID, $clientID);

    //Filter the allowed results for the user, and control if they are repeated
    if (count($results) <> 0) {
        foreach ($results as $item) {
            //Control if the property is visible for the user. If the itemID is new or itemID is the first register, then introduce the register
            if (isPropertyVisible($userID, $item['RS_PROPERTY_ID'], $clientID) AND (($item['RS_ITEM_ID'] <> $lastItemType OR $lastItemType == -1))) {
                $filteredResults[] = array('ID' => $item['RS_ITEM_ID'], 'MAIN' => getItemPropertyValue($item['RS_ITEM_ID'], $mainProperty, $clientID));
                $lastItemType = $item['RS_ITEM_ID'];
            }
        }
    }
    return $filteredResults;
}

//    The PHP receives two arrays with items and orders and updates their order related to passed property.
function reorderItems($clientID, $itemTypeID, $propertyID, $parentID, $idList, $orderList) {
    global $propertiesTables;

    if ($propertyID != 0 && $propertyID != '' ){
        $propertyType = getPropertyType($propertyID, $clientID);
    }

    $failedIDs = array();

    if ($propertyID == 0 || $propertyID == '' ){
        //reordering base itemtype in root, overwrite default order in items
        $result = true;

        for($i=0;$i<count($idList)&&$result;$i++){
            //reorder the element
            $theQuery = "UPDATE `rs_items` SET `RS_ORDER` = '" . $orderList[$i] . "' WHERE `RS_CLIENT_ID` = '" . $clientID . "' AND `RS_ITEMTYPE_ID` = '" . $itemTypeID . "' AND `RS_ITEM_ID` = '" . $idList[$i] . "' LIMIT 1";

            // Query the database
            $result = RSquery($theQuery);

            if (!$result) {
                $failedIDs[] = $idList[$i];
            }
        }

    } elseif (isSingleIdentifier($propertyType)) {
        //reordering single identifiers, overwrite order value in property
        $result=true;
        for($i=0;$i<count($idList)&&$result;$i++){
            //reorder the element
            $theQuery = "UPDATE `" . $propertiesTables[$propertyType] . "` SET `RS_ORDER` = '" . $orderList[$i] . "' WHERE `RS_CLIENT_ID` = '" . $clientID . "' AND `RS_ITEMTYPE_ID` = '" . $itemTypeID . "' AND `RS_ITEM_ID` = '" . $idList[$i] . "' AND `RS_PROPERTY_ID` = '" . $propertyID . "' LIMIT 1";

            // Query the database
            $result = RSquery($theQuery);

            if (!$result) {
                $failedIDs[] = $idList[$i];
            }
        }

    } elseif (isMultiIdentifier($propertyType)) {
        //reordering multiidentifiers, read values list in property and modify order element matching parent value order
        $result=true;
        for($i=0;$i<count($idList)&&$result;$i++) {
            //get property
            $theQuery = "SELECT `RS_DATA`, `RS_ORDER` FROM `" . $propertiesTables[$propertyType] . "` WHERE `RS_CLIENT_ID` = '" . $clientID . "' AND `RS_ITEMTYPE_ID` = '" . $itemTypeID . "' AND `RS_ITEM_ID` = '" . $idList[$i] . "' AND `RS_PROPERTY_ID` = '" . $propertyID . "' LIMIT 1";

            $result = RSquery($theQuery);

            if ($result) {
                $previousProperty = $result->fetch_assoc();
                $parents = explode(',', $previousProperty['RS_DATA']);
                $orders = explode(',', $previousProperty['RS_ORDER']);
                if (count($parents) < count($orders)) {
                    //extra orders: we can't know if they are correct, just cut from the end to match sizes
                    //TO DO: handle error
                    $orders = array_slice($orders, 0, count($parents));
                } elseif (count($parents) > count($orders)) {
                    //missing orders: append zeroes
                    $orders += array_fill(count($orders), count($parents)-count($orders), '0');
                }

                $position = array_search($parentID,$parents);
                if ($position !== false) {
                    //replace order for parent with new value
                    $orders[$position] = $orderList[$i];

                    //save updated order
                    $theQuery = "UPDATE `" . $propertiesTables[$propertyType] . "` SET `RS_ORDER` = '" . implode(',', $orders) . "' WHERE `RS_CLIENT_ID` = '" . $clientID . "' AND `RS_ITEMTYPE_ID` = '" . $itemTypeID . "' AND `RS_ITEM_ID` = '" . $idList[$i] . "' AND `RS_PROPERTY_ID` = '" . $propertyID . "' LIMIT 1";

                    // Query the database
                    $result = RSquery($theQuery);
                } else {
                    $failedIDs[] = $idList[$i];
                }
            }

            if (!$result) {
                $failedIDs[] = $idList[$i];
            }
        }

    } else {
        $results['result'] = "NOK";
        $results['description'] = "Property type not allowed for reordering: " . $propertyType;
    }

    if (!isset($results['result'])) {
        if (count($failedIDs) == 0) {
            $results['result'] = "OK";
        } else {
            $results['result'] = "NOK";
            $results['description'] = "Reorder failed for item IDs: " . implode(',', $failedIDs);
        }
    }

    return $results;
}

//    Returns the default order for item passed.
function getItemOrder($clientID, $itemTypeID, $itemID) {

    // build query
    $theQuery= "SELECT RS_ORDER AS 'ITEM_ORDER' FROM rs_items
                WHERE rs_items.RS_ITEMTYPE_ID = " . $itemTypeID . "
                AND rs_items.RS_CLIENT_ID = " . $clientID . "
                AND rs_items.RS_ITEM_ID = " . $itemID;

    // execute query
    $result = RSQuery($theQuery);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['ITEM_ORDER'];
    } else {
        return '0';
    }
}

//    Returns the order for the property passed.
function getPropertyOrder($itemID, $propertyID, $clientID, $propertyType = '', $itemTypeID = '') {
    global $propertiesTables;

    // If the itemTypeID was not passed... retrieve it
    if ($itemTypeID == '') $itemTypeID = getClientPropertyItemType($propertyID, $clientID);

    // If the property type was not passed... retrieve it
    if ($propertyType == '') $propertyType = getPropertyType($propertyID, $clientID);

    if ($propertyType == 'identifier' || $propertyType == 'identifiers') {
        $result = RSQuery('SELECT RS_ORDER AS "ITEM_ORDER" FROM ' . $propertiesTables[$propertyType] . ' WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID = ' . $itemID . ' AND RS_PROPERTY_ID = ' . $propertyID);

        if ($result && $propertyValue = $result->fetch_assoc()) return $propertyValue['ITEM_ORDER'];

    } else {
        //invalid property type: TO DO handle error
        //error_log("Invalid property type for getPropertyOrder: ".$propertyType);
    }

    return '0';
}

// Recalculate the order value based in old and new property values and old order value
function recalculateOrder($oldValue, $newValue, $oldOrder) {
    $oldValues = explode(',', $oldValue);
    $newValues = explode(',', $newValue);
    $oldOrders = explode(',', $oldOrder);

    if (count($oldValues) < count($oldOrders)) {
        //extra orders: we can't know if they are correct, just cut from the end to match sizes
        //TO DO: handle error
        $oldOrders = array_slice($oldOrders, 0, count($oldValues));
    } elseif (count($oldValues) > count($oldOrders)) {
        //missing orders: append zeroes
        $oldOrders += array_fill(count($oldOrders), count($oldValues)-count($oldOrders), '0');
    }

    //fill with zeroes by default
    $newOrders = array_fill(0, count($newValues), "0");

    foreach ($newValues as $value) {
        $res = array_search($value, $oldValues);

        if ($res !== false) {
            $newOrders[$res] = $oldOrders[$res];
        }
    }

    return implode(',', $newOrders);
}
?>
