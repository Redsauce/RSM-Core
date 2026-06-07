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

if (empty($GLOBALS[$cstRS_POST][$cstClientID])) {
    dieWithError(400);
}

// Optional scope sent by the RSM client UI. Both values must be provided together.
$customerItemTypeID = isset($GLOBALS[$cstRS_POST]['customerItemTypeID']) ? $GLOBALS[$cstRS_POST]['customerItemTypeID'] : 0;
$customerItemID     = isset($GLOBALS[$cstRS_POST]['customerItemID'])     ? $GLOBALS[$cstRS_POST]['customerItemID']     : 0;

if (($customerItemTypeID == 0 && $customerItemID != 0) || ($customerItemTypeID != 0 && $customerItemID == 0)) {
    dieWithError(400);
}

do {
    // We assume the token does not exist
    $exists = false;

    // Let's generate a token (32 chars hex string)
    // We use random_bytes for cryptographic security instead of md5(rand)
    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        // Fallback if random_bytes fails
        $token = md5(uniqid(rand(), true));
    }

    // Ask the database for tokens like the new one
    $results = RScountToken($token);

    // Obtain the data from the query
    if ($results && $result = $results->fetch_assoc()) {
        // Check if we found a token like ours in the database
        if ($result['total'] != 0) {
            $exists = true; // The token is already stored in the database. We must generate a new one
        }
    }

} while ($exists);

// If the execution reaches this point, the token does not exist so we can insert it
// RScreateToken() is defined in Server/htdocs/AppController/commands_RSM/utilities/RSMtokensManagement.php
$results = RScreateToken($token, $GLOBALS[$cstRS_POST][$cstClientID], $customerItemTypeID, $customerItemID);

if (!$results) {
    dieWithError(400);
}

// Generate a response array for RSM
$response['token'] = $token;
$response['customerItemTypeID'] = $customerItemTypeID;
$response['customerItemID'] = $customerItemID;

// And write XML Response back to the application
RSReturnArrayResults($response);
