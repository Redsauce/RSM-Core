<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `RS_USER_ID` , `RS_LOGIN` FROM `rs_users` WHERE `RS_CLIENT_ID` ='".$GLOBALS['RS_POST']['RSclientID']."' ORDER BY `RS_LOGIN` DESC";

//show query if debug mode
if(isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']){
	echo $theQuery;
}

// Query the database
$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
?>
