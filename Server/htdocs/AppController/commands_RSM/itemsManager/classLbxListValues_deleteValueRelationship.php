<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$valueID = $GLOBALS['RS_POST']['valueID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

deleteListValueRelationship_clientSide($valueID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSreturnArrayResults($results);
