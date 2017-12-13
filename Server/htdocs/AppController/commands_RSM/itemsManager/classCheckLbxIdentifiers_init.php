<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$propertyID     = $GLOBALS['RS_POST']['propertyID'    ];
$identifierType = $GLOBALS['RS_POST']['identifierType'];
$clientID       = $GLOBALS['RS_POST']['clientID'      ];

switch ($identifierType) {

    case 'identifier' :
        // get referred item type ID
        $referredItemTypeID = getClientPropertyReferredItemType($propertyID, $clientID);

        // get main property info
        $data = getItems($referredItemTypeID, $clientID);
        $data[0]['itemTypeID'] = $referredItemTypeID;
        break;

    case 'identifiers' :
        // get referred item type ID
        $referredItemTypeID = getClientPropertyReferredItemType($propertyID, $clientID);

        // get main property info
        $data = getItems($referredItemTypeID, $clientID);
        $data[0]['itemTypeID'] = $referredItemTypeID;
        break;

    case 'identifier2itemtype' :
        // get item types
        $itemTypes = getClientItemTypes($clientID);

        $data = array();
        foreach ($itemTypes as $itemType) {
            $data[] = array('ID' => $itemType['ID'], 'mainValue' => $itemType['name']);
        }
        break;

    case 'identifier2property' :
        // get properties
        $properties = getAllVisibleProperties($clientID, $RSuserID, true);

        $data = array();
        foreach ($properties as $property) {
            $data[] = array('ID' => $property['ID'], 'mainValue' => $property['name']);
        }
        break;
}

// Return data
RSReturnArrayQueryResults($data);
?>