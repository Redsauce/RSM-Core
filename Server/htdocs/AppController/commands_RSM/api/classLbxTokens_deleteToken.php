<?php
// ***************************************************************************************
// DESCRIPTION
//     Deletes the token identified for a specified clientID
//
// PARAMETERS
//      token: the string pertaining to the token to delete
//   clientID: ID pertaining to the client that holds the token
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// First of all we must retrieve the ID pertaining to the token
$token    = $GLOBALS['RS_POST']['token'   ];
$clientID = $GLOBALS['RS_POST']['clientID'] ;
$tokenID  = RSgetTokenID($token);

// Check if the token exists.....
if ($tokenID != "") {
    // The token exists. Now we build the query to delete the token properties
    $results = RSdeleteTokenProperties($tokenID, $clientID);

    // Check the query results
    if (!$results) {
        // There was a problem executing the query
        $response['result'     ] = "NOK";
        $response['description'] = "ERROR EXECUTING QUERY TO DELETE TOKEN PROPERTIES";

        // And write XML Response back to the application
        RSReturnArrayResults($response);
    }

    // Now we build the query to delete the tokens
    $results = RSdeleteTokens($token, $clientID);

    // Check the query results
    if (!$results) {
        // There was a problem executing the query
        $response['result'     ] = "NOK";
        $response['description'] = "ERROR EXECUTING QUERY TO DELETE TOKEN";

        // And write XML Response back to the application
        RSReturnArrayResults($response);
    }
}

// Generate a response array for RSM
$response['result'] = "OK";

// And write XML Response back to the application
RSReturnArrayResults($response);
