<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$itemTypeID     =              $GLOBALS['RS_POST']['itemTypeID'    ] ;
$clientID       =              $GLOBALS['RS_POST']['clientID'      ] ;
$itemIDs        = explode(",", $GLOBALS['RS_POST']['itemIDs'       ]);
$propertyIDs    = explode(',', $GLOBALS['RS_POST']['propertyIDs'   ]);
$propertiesList = explode(':', $GLOBALS['RS_POST']['propertiesList']);

$overwrite      = $GLOBALS['RS_POST']['overwrite'] == 1? true : false;
$overwriteQuery = $overwrite ? "REPLACE INTO " : "INSERT INTO ";


$numItems       = count($propertiesList);
$propertyValues = array();

for ($i = 0; $i < $numItems; $i++) $propertyValues[] = explode(' ', $propertiesList[$i]);


if ($GLOBALS['RS_POST']['itemIDs'] == '') {
    // create items without predefined IDs

    // retrieve the first itemID available
    $firstItemID = getNextIdentification('rs_items', 'RS_ITEM_ID', $clientID, array('RS_ITEMTYPE_ID' => $itemTypeID));

    // build items IDs array
    for ($i = 0; $i < $numItems; $i++) $itemIDs[$i] = $firstItemID + $i;


} else {
    // try to create items with predefined IDs

    // check conflict with the items that already exist into the database

    if (!$overwrite) {
        if (count(array_unique($itemIDs)) == count($itemIDs)) {
            // the items ids are unique
            $theQuery_conflicts = 'SELECT RS_ITEM_ID FROM rs_items WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_ITEM_ID IN (' . implode(",", $itemIDs) . ') LIMIT 1';
            $result = RSQuery($theQuery_conflicts);
            if ($result->num_rows > 0) {
                // database conflict
                $results['result'] = 'ERROR1';
                // Write response back to application
                RSReturnArrayResults($results);
            }

        } else {
            // id repeated
            $results['result'] = 'ERROR2';

            // Write response back to application
            RSReturnArrayResults($results);
        }
    }
}

// build the query to create items
for ($i = 0; $i < $numItems; $i++) $theQuery_createItem[] = '(' . $itemTypeID . ',' . $itemIDs[$i] . ',' . $clientID . ')';

$query = $overwriteQuery.'rs_items (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_CLIENT_ID) VALUES ' . implode(",", $theQuery_createItem);

// execute query
if (RSquery($query)) {
    // now we can insert the properties... retrieve the item type properties
    $itemProperties = getClientItemTypeProperties($itemTypeID, $clientID);

    // build the query to insert values
    foreach ($itemProperties as $property) {
        $value = getClientPropertyDefaultValue($property['id'], $clientID);

        $passed = false;

        for ($k = 0; $k < count($propertyIDs); $k++) {
            if ($property['id'] == $propertyIDs[$k]) {
                // The property has been passed by the user
                $passed = true;
                break;
            }
        }

        $theQuery_insertProperties = array();

        for ($i = 0; $i < $numItems; $i++) {
            if ((!$passed) && ($overwrite)) continue; // If we are overwriting we don't store the property if it has not been passed

            if ($passed) $value = base64_decode($propertyValues[$i][$k]);
            if ($property['type'] == 'identifiers') {
                $theQuery_insertProperties[] = '(' . $itemTypeID . ',' . $itemIDs[$i] . ',' . $property['id'] . ',"' . $value . '",' . $clientID . ', "' . implode(',', array_fill(0, count(explode(',',$value)), '0')) . '")';
            } else {
                $theQuery_insertProperties[] = '(' . $itemTypeID . ',' . $itemIDs[$i] . ',' . $property['id'] . ',"' . $value . '",' . $clientID . ')';
            }
        }

        if (count($theQuery_insertProperties) == 0) continue; // If there is nothing to insert, don't execute the query

        if ($property['type'] == 'identifiers') {
            $query = $overwriteQuery . $propertiesTables[$property['type']] . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID, RS_ORDER) VALUES ' . implode(",", $theQuery_insertProperties);
        } else {
            $query = $overwriteQuery . $propertiesTables[$property['type']] . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID) VALUES ' . implode(",", $theQuery_insertProperties);
        }
        // execute query
        if (!RSquery($query)) {
            // query error
            $results['result'] = 'ERROR0';
            $results['query' ] = $query;

            // Write response back to application
            RSReturnArrayResults($results);
        }

    }

    $results['result'] = 'OK';

} else {
    // query error
    $results['result'] = 'ERROR3';
    $results['query' ] = $query;

}

// Write response back to application
RSReturnArrayResults($results);
?>
