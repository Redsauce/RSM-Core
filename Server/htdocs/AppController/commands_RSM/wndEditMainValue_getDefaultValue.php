<?php
// Database connection startup
require_once "utilities/RSdatabase.php";
require_once "utilities/RSMitemsManagement.php";

// definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];

// get default value
$results['defaultValue'] = getClientPropertyDefaultValue(getMainPropertyID($itemTypeID, $clientID), $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
