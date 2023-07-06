<?php
// Database connection startup
require_once "RSdatabase.php";

// Definitions
$url    = base64_decode($GLOBALS['RS_POST']['url']);
$post   = base64_decode($GLOBALS['RS_POST']['post']);
$result = base64_decode($GLOBALS['RS_POST']['result']);

$query = "INSERT INTO `rs_error_log` (`RS_DATE`,`RS_URL`,`RS_POST`,`RS_RESULT`) VALUES (NOW(),'" . $mysqli->real_escape_string($url) . "','" . $mysqli->real_escape_string($post) . "','" . $mysqli->real_escape_string($result) . "')";

// Query the database
if (RSquery($query)) {
    //send mail to admin

    $results['result'] = "OK";
    $results['ID'] = $mysqli->insert_id;
} else {
    //send mail to admin

    $results['result'] = "NOK";
}

// Write XML Response back to the application
RSreturnArrayResults($results);
