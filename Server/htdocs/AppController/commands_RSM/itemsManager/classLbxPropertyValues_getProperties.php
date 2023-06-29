<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$itemID     = $GLOBALS['RS_POST']['itemID'    ];
$clientID   = $GLOBALS['RS_POST']['clientID'  ];
$userID     = $GLOBALS['RS_POST']['loginID'   ];
$getLists   = $GLOBALS['RS_POST']['getLists'  ];

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

$results = getPropertiesExtendedForItemAndUser($itemTypeID, $itemID, $clientID, $userID);

$results[] = array('lists' => '');

if ($getLists == 'true' && !empty($results)) {
    $properties = array();

    foreach ($results as $result) {
        if (isset($result["id"])) {
            $properties[] = $result["id"];
        }
    }

    // build a fast query to get the properties lists
    $theQuery_propertiesList = 'SELECT rs_lists.RS_LIST_ID AS "listID", rs_property_values.RS_VALUE AS "listValue", rs_properties_lists.RS_PROPERTY_ID AS "propertyID", rs_properties_lists.RS_MULTIVALUES AS "multiValues" FROM rs_lists INNER JOIN rs_property_values USING (RS_CLIENT_ID, RS_LIST_ID) INNER JOIN rs_properties_lists USING (RS_CLIENT_ID, RS_LIST_ID) WHERE (rs_lists.RS_CLIENT_ID = ' . $clientID . ') AND (rs_property_values.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_lists.RS_PROPERTY_ID IN (' . ((!empty($properties)) ? (implode(',', $properties)) : ('""')) . ') AND rs_properties_lists.RS_CLIENT_ID = ' . $clientID . ') ORDER BY rs_properties_lists.RS_PROPERTY_ID, rs_property_values.RS_ORDER';

    // execute query
    $theLists = RSquery($theQuery_propertiesList);

    // store info
    while ($row = $theLists->fetch_assoc()) {
        $results[] = $row;
    }
}

// And return XML response back to application
RSReturnArrayQueryResults($results);
