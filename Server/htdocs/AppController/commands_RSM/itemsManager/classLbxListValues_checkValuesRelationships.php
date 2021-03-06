<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$clientValueID = $GLOBALS['RS_POST']['clientValueID'];
$appValueID = $GLOBALS['RS_POST']['appValueID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$appValue = getAppValue($appValueID);
$clientValue = getValue($clientValueID, $clientID);

$appValueIDRelated = getAppListValueID_RelatedWith($clientValueID, $clientID);
$appValueRelated = getAppValue($appValueIDRelated);
$clientValueIDRelated = getClientListValueID_RelatedWith($appValueID, $clientID);
$clientValueRelated = getValue($clientValueIDRelated, $clientID);

$results['appValue'] = $appValue;
$results['clientValue'] = $clientValue;
$results['appValueIDRelated'] = $appValueIDRelated;
$results['appValueRelated'] = $appValueRelated;
$results['clientValueIDRelated'] = $clientValueIDRelated;
$results['clientValueRelated'] = $clientValueRelated;

// And write XML Response back to the application
RSReturnArrayResults($results);
?>