<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$clientListID = $GLOBALS['RS_POST']['clientListID'];
$appListID = $GLOBALS['RS_POST']['appListID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$appListName = getAppListName($appListID);
$clientListName = getListName($clientListID, $clientID);

$appListIDRelated = getAppListIDRelatedWith($clientListID, $clientID);
$appListNameRelated = getAppListName($appListIDRelated);
$clientListIDRelated = getClientListIDRelatedWith($appListID, $clientID);
$clientListNameRelated = getListName($clientListIDRelated, $clientID);

$results['appListName'] = $appListName;
$results['clientListName'] = $clientListName;
$results['appListIDRelated'] = $appListIDRelated;
$results['appListNameRelated'] = $appListNameRelated;
$results['clientListIDRelated'] = $clientListIDRelated;
$results['clientListNameRelated'] = $clientListNameRelated;

// And write XML Response back to the application
RSreturnArrayResults($results);
