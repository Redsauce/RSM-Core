<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Now we build the query
$result = getClientPropertyNameRelatedWith($GLOBALS['RS_POST']['propertyAppID'], $GLOBALS['RS_POST']['clientID']);

$response = array();
$response['propertyClientName'] = $result;

// And write XML Response back to the application
RSreturnArrayResults($response);
