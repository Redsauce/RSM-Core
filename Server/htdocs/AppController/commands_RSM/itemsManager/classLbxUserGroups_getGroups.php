<?php
//***************************************************
//Description:
//  Returns all the groups for a client
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `RS_GROUP_ID` as 'groupID' , `RS_NAME` as 'groupName' FROM `rs_groups` WHERE `RS_CLIENT_ID` ='" . $GLOBALS['RS_POST']['clientID'] . "' ORDER BY `RS_NAME`";

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSreturnQueryResults($results);
