<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$valueID = $GLOBALS[$cstRS_POST][$cstValueID];
$appValueID = $GLOBALS[$cstRS_POST]['appValueID'];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];

createListsValuesRelationship($valueID, $appValueID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
