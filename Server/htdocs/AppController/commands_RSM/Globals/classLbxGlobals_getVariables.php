<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMglobalVariables.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

$results = getGlobalvariables($clientID);

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
?>