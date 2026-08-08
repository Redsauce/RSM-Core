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
//     token: The token itself, as a 32-character random hexadecimal string
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

// Generate secure 32-character tokens until one is not already stored.
do {
    $token = bin2hex(random_bytes(16));
    $countResult = RScountToken($token);
    $countRow = $countResult ? $countResult->fetch_assoc() : null;

    if (!$countRow || !isset($countRow['total'])) {
        dieWithError(400);
    }
} while ((int) $countRow['total'] > 0);

// Insert the generated token.
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
