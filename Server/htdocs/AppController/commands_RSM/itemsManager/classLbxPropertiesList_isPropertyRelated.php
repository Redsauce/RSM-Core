<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$propertyID = $GLOBALS['RS_POST']['propertyID'];

$results['appPropertyID'] = getAppPropertyID_RelatedWith($propertyID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
