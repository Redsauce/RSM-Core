<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `rs_actions`.`rs_id` as 'actionID', `rs_actions`.`rs_name` as 'actionName', `rs_actions`.`rs_description` FROM `rs_actions`";

if($GLOBALS['RS_POST']['applicationName']!=0){
	$theQuery .= " WHERE `rs_actions`.`rs_application_name` = '".$GLOBALS['RS_POST']['RSappName']."'";
}

$theQuery .= " ORDER BY `rs_actions`.`rs_name` DESC";

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
?>