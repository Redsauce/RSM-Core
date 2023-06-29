<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$listID = $GLOBALS['RS_POST']['listID'];
$clientID = $GLOBALS['RS_POST']['clientID'];


// get list values
$results = getListValues($listID, $clientID);

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
