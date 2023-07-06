<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT rs_item_type_app_definitions.RS_NAME AS 'itemTypeAppName' FROM rs_item_type_app_definitions INNER JOIN rs_item_type_app_relations ON rs_item_type_app_definitions.RS_ID = rs_item_type_app_relations.RS_ITEMTYPE_APP_ID WHERE rs_item_type_app_relations.RS_ITEMTYPE_ID = '" . $GLOBALS['RS_POST']['itemTypeID'] . "' AND rs_item_type_app_relations.RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'";

//show query if debug mode
if (isset($GLOBALS['RS_POST']['RSdebug']) && $GLOBALS['RS_POST']['RSdebug']) {
    echo $theQuery;
}

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSreturnQueryResults($results);
