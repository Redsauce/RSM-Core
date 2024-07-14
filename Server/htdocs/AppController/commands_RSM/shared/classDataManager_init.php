<?php
//***************************************************
// Description:
//  Init
//
// parameters:
// clientID       => the client ID
// properties  => the properties (system properties or user properties)
// getSetOfValues => '0' if the list values or identifiers are not required, '1' elsewhere
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID       =              $GLOBALS['RS_POST']['clientID'      ];
$sysProperties  = explode(",", $GLOBALS['RS_POST']['properties'    ]);
$getSetOfValues =              $GLOBALS['RS_POST']['getSetOfValues'];

// Get item type ID
$itemTypeID = getItemTypeIDFromProperties($sysProperties, $clientID);

if ($itemTypeID <= 0) {
    // The properties does not pertain to the same item type
    RSReturnError("PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE", 0);
}

$itemTypeName        = getClientItemTypeName         ($itemTypeID    , $clientID);
$itemTypeIcon        = getClientItemTypeIcon         ($itemTypeID    , $clientID);
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

// Get user visible properties
$visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);

$appProperties  = array();
$userProperties = array();

foreach ($sysProperties as $sysProperty) is_numeric($sysProperty)? $userProperties[] = $sysProperty : $appProperties[] = $sysProperty;

// Get properties required
$propertiesUser = getPropertyIDs             ($userProperties, $clientID);
$propertiesApp  = getPropertyIDs_usingSysName($appProperties , $clientID);

// Merge the properties
$properties = array();
$properties = array_merge($propertiesApp, $propertiesUser);

// Return the properties that are required and visible
foreach ($properties as $property) {

    if (!in_array($property["ID"], $visibleProperties)) continue; // property is not visible

    // Add the propertyName in the results, if found
    $property["appName"] = getAppPropertyName_RelatedWith($property["ID"], $clientID);

    // add the property to the results
    $results[] = $property;

    if ($list = getPropertyList($property['ID'], $clientID)) {
        // add mode (multivalues: 0-1) to the results
        // If the property is related with an app list, return the appList ID and name
        $results[] = array('listID' => $list['listID'], 'listValues' => $list['multiValues'], 'appListName' => getAppListName(getAppListID_RelatedWith($list['listID'], $clientID), $clientID));

        if ($getSetOfValues == '1') {
            // get list values
            $listValues = getListValues($list['listID'], $clientID);

            foreach ($listValues as $value) {
                $results[] = array('value' => $value['value'], 'id' => $value['valueID'], 'appName' => getAppValue(getAppListValueID_RelatedWith($value['valueID'], $clientID)));
            }
        }

    } else {

        if ($getSetOfValues == '1') {

            if (isSingleIdentifier($property['type']) || isMultiIdentifier($property['type'])) {
                // --- return the list of identifiers ---

                // add mode (multiIdentifier: 0-1) to the results
                isSingleIdentifier($property['type'])? $results[] = array('idsValues' => '0') : $results[] = array('idsValues' => '1');

                // get property referred item type
                $filterProperties   = array();
                $returnProperties   = array();
                $referredItemTypeID = getClientPropertyReferredItemType($property['ID']    , $clientID);
                $returnProperties[] = array('ID' => getMainPropertyID  ($referredItemTypeID, $clientID), 'name' => 'mainValue');
                $referredItems      = IQ_getFilteredItemsIDs           ($referredItemTypeID, $clientID , $filterProperties, $returnProperties, 'mainValue');

                while ($row = $referredItems->fetch_assoc()) $results[] = $row;

            } elseif (isIdentifier2itemtype($property['type'])) {

                // add mode (0) to the results
                $results[] = array('idsValues' => '0');

                // get item types list
                $itemTypes = getClientItemTypes($clientID);

                foreach ($itemTypes as $itemType) {
                    $results[] = array('ID' => $itemType['ID'], 'mainValue' => $itemType['name']);
                }

            } elseif (isIdentifier2property($property['type'])) {

                // add mode (0) to the results
                $results[] = array('idsValues' => '0');

                // get properties list
                $properties = getAllVisibleProperties($clientID, $RSuserID, true);

                foreach ($properties as $property) {
                    $results[] = array('ID' => $properties['ID'], 'mainValue' => $properties['name']);
                }
            }
        }
    }
}

// Return results
RSReturnArrayQueryResults($results);
?>