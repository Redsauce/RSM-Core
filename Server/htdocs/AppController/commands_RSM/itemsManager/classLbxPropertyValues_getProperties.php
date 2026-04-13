<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RStools.php';

// definitions
isset($GLOBALS[$cstRS_POST][$cstClientID  ]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemID    ]) ? $itemID = $GLOBALS[$cstRS_POST][$cstItemID] : dieWithError(400);
(isset($GLOBALS[$cstRS_POST]["getLists"]) && $GLOBALS[$cstRS_POST]["getLists"] == 'true') ? $getLists = 'true' : $getLists = '';

// If the passed item type is a system property, get the numeric ID
// This function will return an ID also if an ID is passed
$itemTypeID = parseITID($itemTypeID, $clientID);

// Use token-based properties if RStoken is provided, otherwise use user-based
$RStoken = isset($GLOBALS[$cstRS_POST]['RStoken']) ? $GLOBALS[$cstRS_POST]['RStoken'] : '';

if ($RStoken != '') {
    $results = getPropertiesExtendedForItemAndToken($itemTypeID, $itemID, $RStoken);
} else {
    $userID = RSCheckUserAccess();
    $results = getPropertiesExtendedForItemAndUser($itemTypeID, $itemID, $clientID, $userID);
}

$results[] = array('lists' => '');

if ($getLists == 'true' && count($results) > 0) {
	$properties = array();

    foreach ($results as $result) {
        if (isset($result["id"])) {
            $properties[] = $result["id"];
        }
    }

	// build a fast query to get the properties lists
	$theQuery_propertiesList = 'SELECT rs_lists.RS_LIST_ID AS "listID", rs_property_values.RS_VALUE AS "listValue", rs_properties_lists.RS_PROPERTY_ID AS "propertyID", rs_properties_lists.RS_MULTIVALUES AS "multiValues" FROM rs_lists INNER JOIN rs_property_values USING (RS_CLIENT_ID, RS_LIST_ID) INNER JOIN rs_properties_lists USING (RS_CLIENT_ID, RS_LIST_ID) WHERE (rs_lists.RS_CLIENT_ID = ' . $clientID . ') AND (rs_property_values.RS_CLIENT_ID = ' . $clientID . ') AND (rs_properties_lists.RS_PROPERTY_ID IN (' . ((count($properties) > 0) ? (implode(',', $properties)) : ('""')) . ') AND rs_properties_lists.RS_CLIENT_ID = ' . $clientID . ') ORDER BY rs_properties_lists.RS_PROPERTY_ID, rs_property_values.RS_ORDER';

	// execute query
	$theLists = RSquery($theQuery_propertiesList);

	// store info
	while ($row = $theLists->fetch_assoc()) {
		$results[] = $row;
	}
}

// And return XML response back to application
RSReturnArrayQueryResults($results);