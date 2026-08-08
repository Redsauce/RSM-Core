<?php
// ***************************************************************************************
// DESCRIPTION
//     Creates a token and returns the token ID and the token string. The new token will be
//   deactivated by default and won't have permissions to work with any item types.
//
// PARAMETERS
//              clientID: Client that will own the token
//       isMasterTemplate: Optional true to create a master template
//      parentMasterToken: Optional master token string used to create a child
//    parentMasterTokenID: Optional legacy numeric master ID used to create a child
//
// RETURN
//        ID: Client-local numeric ID of the created token
//     token: The token itself, as a 32-character random hexadecimal string
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

$clientID = RSrequireTokenManagementAccess();

// Optional scope sent by the RSM client UI. Both values must be provided together.
$customerItemTypeID = isset($GLOBALS[$cstRS_POST]['customerItemTypeID']) ? $GLOBALS[$cstRS_POST]['customerItemTypeID'] : 0;
$customerItemID     = isset($GLOBALS[$cstRS_POST]['customerItemID'])     ? $GLOBALS[$cstRS_POST]['customerItemID']     : 0;
$isMasterTemplate   = isset($GLOBALS[$cstRS_POST]['isMasterTemplate'])   ? filter_var($GLOBALS[$cstRS_POST]['isMasterTemplate'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : false;
$hasParentMasterToken = isset($GLOBALS[$cstRS_POST]['parentMasterToken']);
$parentMasterToken = $hasParentMasterToken ? $GLOBALS[$cstRS_POST]['parentMasterToken'] : '';
$parentMasterTokenID = isset($GLOBALS[$cstRS_POST]['parentMasterTokenID']) ? $GLOBALS[$cstRS_POST]['parentMasterTokenID'] : 0;

if (($customerItemTypeID == 0 && $customerItemID != 0) || ($customerItemTypeID != 0 && $customerItemID == 0)) {
    dieWithError(400);
}
if (is_null($isMasterTemplate) || !is_numeric($parentMasterTokenID) || intval($parentMasterTokenID) < 0) {
    dieWithError(400);
}
$parentMasterTokenID = intval($parentMasterTokenID);
if ($hasParentMasterToken) {
    if (!is_string($parentMasterToken) || $parentMasterToken === '' || $parentMasterTokenID > 0) {
        dieWithError(400);
    }

    $parentMasterTokenID = RSgetMasterTokenID($parentMasterToken, $clientID);
    if ($parentMasterTokenID <= 0) {
        dieWithError(400);
    }
}
if ($isMasterTemplate && $parentMasterTokenID > 0) {
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
$results = RScreateToken($token, $clientID, $customerItemTypeID, $customerItemID, $isMasterTemplate, $parentMasterTokenID);

if (!$results) {
    dieWithError(400);
}

// Generate a response array for RSM
$response['ID'] = RSgetTokenID($token);
$response['token'] = $token;
$response['customerItemTypeID'] = $customerItemTypeID;
$response['customerItemID'] = $customerItemID;
$response['isMasterTemplate'] = $isMasterTemplate ? 1 : 0;
$response['parentMasterTokenID'] = $parentMasterTokenID;

// And write XML Response back to the application
RSReturnArrayResults($response);
