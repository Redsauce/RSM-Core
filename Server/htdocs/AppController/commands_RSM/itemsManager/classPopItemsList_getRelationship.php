<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT rs_item_types.RS_ITEMTYPE_ID AS 'itemTypeID' FROM rs_item_types INNER JOIN rs_item_type_app_relations ON rs_item_types.RS_ITEMTYPE_ID = rs_item_type_app_relations.RS_ITEMTYPE_ID and rs_item_types.RS_CLIENT_ID = rs_item_type_app_relations.RS_CLIENT_ID where rs_item_type_app_relations.RS_ITEMTYPE_APP_ID = '".$GLOBALS['RS_POST']['itemTypeAppID']."' AND rs_item_type_app_relations.RS_CLIENT_ID = '".$GLOBALS['RS_POST']['clientID']."'";

// Query the database
$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
