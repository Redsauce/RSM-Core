<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT rs_item_type_app_definitions.RS_NAME AS 'itemTypeAppName' FROM rs_item_type_app_definitions INNER JOIN rs_item_type_app_relations ON rs_item_type_app_definitions.RS_ID = rs_item_type_app_relations.RS_ITEMTYPE_APP_ID WHERE rs_item_type_app_relations.RS_ITEMTYPE_ID = '".$GLOBALS[$cstRS_POST][$cstItemTypeID]."' AND rs_item_type_app_relations.RS_CLIENT_ID = '".$GLOBALS[$cstRS_POST][$cstClientID]."'";

//show query if debug mode
if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']){
	echo $theQuery;
}

// Query the database
$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
