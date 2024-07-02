<?php
//***************************************************
// Description:
//	Init
//
// parameters:
// clientID   	  => the client ID
// itemType   	  => the item type system name or the client item type ID
// getSetOfValues => '0' if the list values or identifiers are not required, '1' elsewhere
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID       = $GLOBALS['RS_POST']['clientID'      ];
$itemType       = $GLOBALS['RS_POST']['itemType'      ];
$getSetOfValues = $GLOBALS['RS_POST']['getSetOfValues'];

// Get item type ID
$itemTypeID   = parseITID($itemType, $clientID);

$itemTypeName        = getClientItemTypeName         ($itemTypeID    , $clientID);
$itemTytpeIcon       = getClientItemTypeIcon         ($itemTypeID    , $clientID);
$mainPropertyID      = getMainPropertyID             ($itemTypeID    , $clientID);
$mainPropertyName    = getClientPropertyName         ($mainPropertyID, $clientID);
$mainPropertyAppName = getAppPropertyName_RelatedWith($mainPropertyID, $clientID);

// prepare results array
$results   = array();
$results[] = array(
    'itemTypeID'          => $itemTypeID,
    'itemTypeIcon'        => $itemTypeIcon,
    'itemTypeName'        => $itemTypeName,
    'mainPropertyID'      => $mainPropertyID,
    'mainPropertyName'    => $mainPropertyName,
    'mainPropertyAppname' => $mainPropertyAppName
);



// prepare results array
$results   = array();
$results[] = array(
    'itemTypeID'          => $itemTypeID,
    'itemTypeName'        => $itemTypeName,
    'itemTypeIcon'        => $itemTypeIcon,
    'mainPropertyID'      => $mainPropertyID,
    'mainPropertyName'    => $mainPropertyName,
    'mainPropertyAppname' => $mainPropertyAppName
);

// Get user visible properties
$visibleProperties = getUserVisibleProperties($itemTypeID, $clientID, $RSuserID);

// Return the properties that are required and visible
foreach ($visibleProperties as $property) {

    // add the property to the results
    unset($property['categoryID']);
    unset($property['categoryName']);

    // Add the propertyName in the results, if found
    $property["appName"] = getAppPropertyName_RelatedWith($property["propertyID"], $clientID);

    $results[] = $property;

    if ($list = getPropertyList($property['propertyID'], $clientID)) {
        // add mode (multivalues: 0-1) to the results
        $results[] = array('listID' => $list['listID'], 'listValues' => $list['multiValues']);

        if ($getSetOfValues == '1') {
            // get list values
            $listValues = getListValues($list['listID'], $clientID);

            foreach ($listValues as $value) {
                $results[] = array('value' => $value['value']);
            }
        }
    } else {

        if ($getSetOfValues == '1') {

            if (isSingleIdentifier($property['propertyType']) || isMultiIdentifier($property['propertyType'])) {
                // --- return the list of identifiers ---

                // add mode (multiIdentifier: 0-1) to the results
                isSingleIdentifier($property['propertyType'])? $results[] = array('idsValues' => '0') :  $results[] = array('idsValues' => '1');

                // get property referred item type
                $filterProperties   = array();
                $returnProperties   = array();
                $referredItemTypeID = getClientPropertyReferredItemType($property['propertyID'], $clientID);
                $returnProperties[] = array('ID' => getMainPropertyID  ($referredItemTypeID    , $clientID), 'name' => 'mainValue');
                $referredItems      = IQ_getFilteredItemsIDs           ($referredItemTypeID    , $clientID , $filterProperties, $returnProperties, 'mainValue');

                while ($row = $referredItems->fetch_assoc()) $results[] = $row;

            } elseif (isIdentifier2itemtype($property['propertyType'])) {

                // add mode (0) to the results
                $results[] = array('idsValues' => '0');

                // get item types list
                $itemTypes = getClientItemTypes($clientID);

                foreach ($itemTypes as $itemType) {
                    $results[] = array('ID' => $itemType['ID'], 'mainValue' => $itemType['name']);
                }

            } elseif (isIdentifier2property($property['propertyType'])) {

                // add mode (0) to the results
                $results[] = array('idsValues' => '0');

                // get properties list
                $allVisibleProperties = getAllVisibleProperties($clientID, $RSuserID, true);

                foreach ($allVisibleProperties as $visibleProperty) $results[] = array('ID' => $visibleProperty['ID'], 'mainValue' => $visibleProperty['name']);
            }
        }
    }
}

// Return results
RSReturnArrayQueryResults($results);
?>
