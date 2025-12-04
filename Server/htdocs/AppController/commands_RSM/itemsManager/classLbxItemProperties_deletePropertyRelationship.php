<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = '".$GLOBALS[$cstRS_POST][$cstPropertyClientID]."' AND RS_CLIENT_ID = '".$GLOBALS[$cstRS_POST][$cstClientID]."'";

//show query if debug mode
if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug']){
		echo ($theQuery . "\n\n");
}

// Query the database
$results = RSQuery($theQuery);

$response = array();
$response['result'] = ($results == TRUE) ? "OK" : "NOK";

// And write XML Response back to the application
RSReturnArrayResults($response);
?>
