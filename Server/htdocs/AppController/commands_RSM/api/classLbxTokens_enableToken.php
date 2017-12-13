<?php
// ***************************************************************************************
// DESCRIPTION
//      Enables the token pertaining to the tokenID passed as parameter
// 
// PARAMETERS
//   clientID: ID of the client who is owner of the token
//      token: String pertaining to the token to disable
//
// RETURN
//   result: OK
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// Set the token as enabled in the database
$results = RSenableToken($GLOBALS['RS_POST']['token'],$GLOBALS['RS_POST']['clientID']);

// Generate a response array for RSM
if (!$results) {
     // There was a problem executing the query
     $response['result'] = "NOK";

} else {
     // The query was successfully executed
    $response['result'] = "OK";
}

// And write XML Response back to the application
RSReturnArrayResults($response);
?>