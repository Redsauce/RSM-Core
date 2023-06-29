<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `RS_ITEMTYPE_ID`, `RS_NAME`, `RS_ICON` FROM `rs_item_types` WHERE `RS_CLIENT_ID`='" . $GLOBALS['RS_POST']["clientID"] . "' ORDER BY `RS_ORDER`";

// Query the database
$result = RSquery($theQuery);

$results = array();

if ($result) {
    while ($row=$result->fetch_assoc()) {
        $results[]=array('ID'=>$row['RS_ITEMTYPE_ID'], 'NAME'=>$row['RS_NAME'], 'ICON'=>bin2hex($row['RS_ICON']));
    }
}

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
