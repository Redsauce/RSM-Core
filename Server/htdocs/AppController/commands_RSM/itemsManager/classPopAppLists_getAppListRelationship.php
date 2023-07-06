<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$appListID = $GLOBALS['RS_POST']['appListID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$clientListID = getClientListIDRelatedWith($appListID, $clientID);

$result['clientListID'] = $clientListID;

// And write XML Response back to the application
RSreturnArrayResults($result);
