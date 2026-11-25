<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMmediaManagement.php";

// definitions
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$categoryID = $GLOBALS[$cstRS_POST][$cstCategoryID];


deleteClientCategory($categoryID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
