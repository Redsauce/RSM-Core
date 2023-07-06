<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$appValueID = $GLOBALS['RS_POST']['appValueID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$results['clientValueID'] = getClientListValueIDRelatedWith($appValueID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
