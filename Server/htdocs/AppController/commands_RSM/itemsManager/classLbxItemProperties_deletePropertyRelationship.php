<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = '" . $GLOBALS['RS_POST']['propertyClientID'] . "' AND RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "'";

//show query if debug mode
if (isset($GLOBALS['RS_POST']['RSdebug']) && $GLOBALS['RS_POST']['RSdebug']) {
        echo $theQuery . "\n\n";
}

// Query the database
$results = RSquery($theQuery);

$response = array();
$response['result'] = $results ? "OK" : "NOK";

// And write XML Response back to the application
RSreturnArrayResults($response);
