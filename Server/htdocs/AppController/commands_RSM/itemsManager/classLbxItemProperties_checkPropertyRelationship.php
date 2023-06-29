<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT rs_property_app_definitions.RS_NAME AS 'propertyAppName' FROM rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID WHERE RS_PROPERTY_ID = '".$GLOBALS['RS_POST']['propertyClientID']."' AND RS_CLIENT_ID = ".$GLOBALS['RS_POST']['clientID'];

//show query if debug mode
if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
    echo $theQuery . "\n\n";
}

// Query the database
$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
