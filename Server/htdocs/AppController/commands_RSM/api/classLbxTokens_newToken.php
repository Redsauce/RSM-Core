<?php
// ***************************************************************************************
// DESCRIPTION
//     Creates a token and returns the token ID and the token string. The new token will be
//   deactivated by default and won't have permissions to work with any item types.
//
// PARAMETERS
//   The only needed parameter is the clientID that is already passed in every petition
//
// RETURN
//     token: The token itself, as a 32-character string (MD5 hash)
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

if (empty($GLOBALS['RS_POST']['clientID'])) {
    dieWithError(400);
}

// We will use this variable in order to control if the new token exists or not
$exists = false;

do {
    // Let's generate a token
    $token = md5(generateRandomString(256));

    // Ask the database for tokens like the new one
    $results = RScountToken($token);

    // Obtain the data from the query
    if ($results) {
        $result = $results->fetch_assoc();
    }
    
    // Check if we found a token like ours in the database
    if ($result['total'] <> 0) {
        $exists = true; // The token is already stored in the database. We must generate a new one
    }

} while ($exists);

// If the execution reaches this point, the token does not exist so we can insert it
$results = RScreateToken($token, $GLOBALS['RS_POST']['clientID']);

// Generate a response array for RSM
$response['token'] = $token;

// And write XML Response back to the application
RSreturnArrayResults($response);

// This function generates a random string of the given length
function generateRandomString($length = 10)
{
    // This is the list of characters allowed inside the generated string
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        // We loop until the desired length is reached, adding random characters
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }

    // And return the result
    return $randomString;
}
