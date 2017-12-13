<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$listID = $GLOBALS['RS_POST']['listID'];
$appListID = $GLOBALS['RS_POST']['appListID'];

createListsRelationship($listID, $appListID, $clientID);

$result['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($result);
?>