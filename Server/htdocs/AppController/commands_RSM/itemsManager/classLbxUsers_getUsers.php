<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

$clientID = $GLOBALS['RS_POST']['clientID'];

// Now we build the query
$results = RSquery("SELECT RS_USER_ID as 'userID', RS_LOGIN as 'userLogin', RS_ITEM_ID as 'staffID', RS_BADGE as 'userBadge' FROM rs_users WHERE RS_CLIENT_ID = " . $clientID . " ORDER BY RS_LOGIN DESC");

// And write XML Response back to the application
RSreturnQueryResults($results);
