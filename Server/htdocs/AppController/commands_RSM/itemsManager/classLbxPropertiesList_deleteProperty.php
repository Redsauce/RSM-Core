<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";


$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$propertyID = $GLOBALS[$cstRS_POST][$cstPropertyID];

deleteClientProperty($propertyID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
?>