<?php
//
// classLbxFilteredItemProperties_deleteItems.php

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$ids = $GLOBALS['RS_POST']['itemIDs'];
$force = $GLOBALS['RS_POST']['force'];

if ($force != 1) {

    // --- check the relationships ---
    // get item types
    $itemTypes = getClientItemTypes($clientID);

    foreach ($itemTypes as $itemType) {
        // get item type properties
        $propertiesList = getClientItemTypeProperties($itemType['ID'], $clientID);

        foreach ($propertiesList as $property) {
            if (isSingleIdentifier($property['type'])) {
                if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                    $query = RSquery('SELECT RS_ITEM_ID FROM rs_property_identifiers WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND RS_DATA IN (' . $ids . ') LIMIT 1');
                    if ($query->num_rows > 0) {
                        $results['result'] = 'NOK';

                        // Write XML Response back to the application
                        RSreturnArrayResults($results);
                        exit;
                    }
                }
            } elseif (isMultiIdentifier($property['type'])) {
                if (getClientPropertyReferredItemType($property['id'], $clientID) == $itemTypeID) {
                    $idsArr = explode(',', $ids);
                    $conditions = array();
                    foreach ($idsArr as $id) {
                        $conditions[] = 'FIND_IN_SET("' . $id . '", RS_DATA) > 0';
                    }
                    $query = RSquery('SELECT RS_ITEM_ID FROM rs_property_multiIdentifiers WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemType['ID'] . ' AND RS_PROPERTY_ID = ' . $property['id'] . ' AND (' . implode(' OR ', $conditions) . ') LIMIT 1');
                    if ($query->num_rows > 0) {
                        $results['result'] = 'NOK';

                        // Write XML Response back to the application
                        RSreturnArrayResults($results);
                        exit;
                    }
                }
            }
        }
    }
}

// --- delete items ---
if (strpos($ids, ',') === false) {
    deleteItem($itemTypeID, $ids, $clientID);
} else {
    deleteItems($itemTypeID, $clientID, $ids);
}

$results['result'] = 'OK';

// And write XML Response back to the application
RSreturnArrayResults($results);
