<?php
// health_check.php

require_once "RSconfiguration.php";

// Define the response structure
$response = array(
    'status' => 'ok',
    'causes' => array(),
    'timestamp' => time()
);

$errorHeaderMessage = $_SERVER['SERVER_PROTOCOL'] . ' 500 INTERNAL SERVER ERROR';

// Check the status of your application
// Add your application-specific health checks here
// For example, check the database connection, external services, etc.
$dbConnCheck = checkDatabaseConnectivityStatus($RShost, $RSuser, $RSpassword, $RSdatabase);
if (!$dbConnCheck['success']) {
    header($errorHeaderMessage, true, 500);
    $response['status'] = 'ko';
    $response['causes']['dbConnectivity'] = $dbConnCheck['message'];
}

// Encode the response as JSON
$response = json_encode($response);

// Set the appropriate headers for the health check response
header("Content-Type: application/json");
Header("Content-Length: " . strlen($response));

// Finally send the response
echo $response;

// Function to check the database connectivity
function checkDatabaseConnectivityStatus($RShost, $RSuser, $RSpassword, $RSdatabase){
    $result = array(
        'success' => true,
        'message' => 'connection alive'
    );
    // Connect to the database using the above settings
    $mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
    if ($mysqli == false || $mysqli->connect_errno) {
        error_log('CANNOT CONNECT TO: ' . $RShost . '[' . $RSdatabase . ']');
        $result['success'] = false;
        $result['message'] = $mysqli->connect_error;
    }
    $mysqli->close();
    return $result;
}
?>
