<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];


deleteClientItemType($itemTypeID, $clientID);

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
