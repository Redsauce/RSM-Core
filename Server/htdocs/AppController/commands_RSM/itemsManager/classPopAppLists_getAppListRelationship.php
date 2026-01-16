<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$appListID = $GLOBALS[$cstRS_POST]['appListID'];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];

$clientListID = getClientListID_RelatedWith($appListID, $clientID);

$result['clientListID'] = $clientListID;

// And write XML Response back to the application
RSReturnArrayResults($result);
