<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$listID = $GLOBALS[$cstRS_POST][$cstListID];
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];


// get list values
$results = getListValues($listID, $clientID);

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
?>