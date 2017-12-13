<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';

// get client name and default logo
$result = RSquery("SELECT RS_NAME, RS_LOGO FROM rs_clients WHERE RS_ID = " . $GLOBALS['RS_POST']['clientID']);

// build results array
$results = array();

if ($result) {
    $clientInfo = $result->fetch_assoc();

    // put the client name and logo into the results array
    $results[] = array('clientName' => $clientInfo['RS_NAME'], 'clientLogo' => bin2hex($clientInfo['RS_LOGO']));
}

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
?>
