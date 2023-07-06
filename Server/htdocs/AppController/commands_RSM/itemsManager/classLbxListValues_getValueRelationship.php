<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$valueID = $GLOBALS['RS_POST']['valueID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$results['appValueID'] = getAppListValueID_RelatedWith($valueID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
