<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Now we build the query
$theQuery = "SELECT `RS_ID` AS 'id', `RS_NAME` AS 'name', `RS_TYPE` AS 'type' FROM `rs_property_app_definitions` WHERE `RS_ITEM_TYPE_ID` = '" . $GLOBALS['RS_POST']['item_type_id'] . "' ORDER BY name";

// Query the database
$theProperties = RSquery($theQuery);

$data = array();

if ($theProperties) {
    while ($theProperty = $theProperties->fetch_assoc()) {
        $clientPropertyID = getClientPropertyID_RelatedWith($theProperty['id'], $GLOBALS['RS_POST']['clientID']);
        if ($clientPropertyID != '0') {
            $related = '1';
        } else {
            $related = '0';
        }
        $data[] = array("id" => $theProperty['id'], "name" => $theProperty['name'], "type" => $theProperty['type'], "related" => $related);
    }
}

// And write XML Response back to the application
RSreturnArrayQueryResults($data);
