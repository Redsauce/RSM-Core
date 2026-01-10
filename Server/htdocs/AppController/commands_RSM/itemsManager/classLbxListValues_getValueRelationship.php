<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";


$valueID = $GLOBALS[$cstRS_POST][$cstValueID];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];

$results['appValueID'] = getAppListValueID_RelatedWith($valueID, $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
