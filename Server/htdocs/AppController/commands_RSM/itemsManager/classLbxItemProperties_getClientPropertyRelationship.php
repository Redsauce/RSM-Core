<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Now we build the query
$result = getAppPropertyID_RelatedWith($GLOBALS[$cstRS_POST][$cstPropertyClientID], $GLOBALS[$cstRS_POST][$cstClientID]);

$response = array();
$response['propertyAppID'] = $result;

// And write XML Response back to the application
RSReturnArrayResults($response);
