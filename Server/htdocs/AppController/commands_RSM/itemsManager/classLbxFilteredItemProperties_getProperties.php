<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RSMlistsManagement.php';

$clientID   = $GLOBALS['RS_POST']['clientID'];
$userID     = $GLOBALS['RS_POST']['userID'];
$itemTypeID = $GLOBALS['RS_POST']['itemtypeID'];
$showIDs    = $GLOBALS['RS_POST']['showIDs'];

$passedProperties = explode(',', $GLOBALS['RS_POST']['propertyIDs']);

// generate arrays
for ($i = 0; $i < count($passedProperties); $i++) {
    // retrieve the property ID and the filter
    $entry = explode(';', $passedProperties[$i]);

    // add to the arrays
    $propertyIDs[$i] = $entry[0];
    $propertyFilters[$i] = base64_decode($entry[1]);
}

// build a fast query to get user properties
$theQuery_getProperties = 'SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID AS "propertyID", rs_item_properties.RS_NAME AS "propertyName" FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND rs_categories.RS_CLIENT_ID = ' . $clientID . ') AND (rs_item_properties.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_groups.RS_CLIENT_ID = ' . $clientID . ') AND (rs_users_groups.RS_USER_ID = ' . $userID . ' AND rs_users_groups.RS_CLIENT_ID = ' . $clientID . ')';

// execute query
$theProperties = RSquery($theQuery_getProperties);

$filterProperties = array();
$returnProperties = array();

if ($theProperties) {
    // save results into an associative array
    while ($row = $theProperties->fetch_assoc()) {
        $allowedPropertiesList[$row['propertyID']] = $row['propertyName'];
    }
}

// build the filter and the return properties arrays
for ($i = 0; $i < count($propertyIDs); $i++) {
    if (isset($allowedPropertiesList[$propertyIDs[$i]])) {
        if ($propertyFilters[$i] != '') {

            // get property type
            $propertyType = getPropertyType($propertyIDs[$i], $clientID);

            if ((isSingleIdentifier($propertyType)) || (isIdentifierToItemtype($propertyType)) || (isIdentifierToProperty($propertyType))) {
                $filterProperties[] = array('ID' => $propertyIDs[$i], 'value' => $propertyFilters[$i]);
            } elseif (isMultiIdentifier($propertyType)) {

                $ids = explode(',', $propertyFilters[$i]);
                foreach ($ids as $id) {
                    $filterProperties[] = array('ID' => $propertyIDs[$i], 'value' => $id, 'mode' => 'IN');
                }
            } elseif (getPropertyList($propertyIDs[$i], $clientID) && $propertyType != "text") { //If $propertyIDs[$i] is a list with multiValue
                $auxiliar = ",";
                $ids = explode(';', $propertyFilters[$i]);
                foreach ($ids as $id) {
                    $auxiliar = $auxiliar . "'" . ltrim($id) . "',";
                }
                $auxiliar = trim($auxiliar, ',');
                $filterProperties[] = array('ID' => $propertyIDs[$i], 'value' => $auxiliar, 'mode' => '<-IN');
            } else {
                $filterProperties[] = array('ID' => $propertyIDs[$i], 'value' => '%' . $propertyFilters[$i] . '%', 'mode' => 'LIKE');
            }
        }
        $returnProperties[] = array('ID' => $propertyIDs[$i], 'name' => base64_encode($allowedPropertiesList[$propertyIDs[$i]]), 'trName' => base64_encode($allowedPropertiesList[$propertyIDs[$i]] . "_tr"));
    }
}

// get items and properties
$totalData = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);

// And write XML Response back to the application
RSreturnArrayQueryResults($totalData);
