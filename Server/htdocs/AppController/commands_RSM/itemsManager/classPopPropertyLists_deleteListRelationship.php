<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$appListID = $GLOBALS[$cstRS_POST]['appListID'];

deleteListRelationship_appSide($appListID, $clientID);

$result['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($result);
?>

