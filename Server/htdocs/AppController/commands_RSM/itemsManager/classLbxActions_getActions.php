<?php
//***************************************************
//Description:
//  Returns all actions for the RSM application
// ---> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `rs_actions`.`RS_ID`, `rs_actions`.`RS_NAME`, `rs_actions`.`RS_DESCRIPTION` FROM `rs_actions`";

if ($GLOBALS['RS_POST']['applicationName']!=0) {
    $theQuery .= " WHERE `rs_actions`.`RS_APPLICATION_NAME` = '".$GLOBALS['RS_POST']['applicationName']."'";
}


$theQuery .= " ORDER BY `rs_actions`.`RS_NAME` DESC";

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
