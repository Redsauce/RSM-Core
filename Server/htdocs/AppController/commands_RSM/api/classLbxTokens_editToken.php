<?php
// ***************************************************************************************
// DESCRIPTION
//     Edits an existing token's customer scope and optional alias.
//
// PARAMETERS
//      token: String pertaining to the token to edit
// itemTypeID: Optional customer item type ID scoped by this token
//     itemID: Optional customer item ID scoped by this token
// tokenAlias: Optional alias to store for this token
//
// RETURN
//   result: OK or NOK
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$response = array();

$RStoken = isset($GLOBALS[$cstRS_POST]['token']) ? $GLOBALS[$cstRS_POST]['token'] : "";
$clientID = $RStoken != "" ? RSclientFromToken($RStoken) : 0;

$hasItemTypeID = isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) && $GLOBALS[$cstRS_POST][$cstItemTypeID] !== "";
$hasItemID = isset($GLOBALS[$cstRS_POST]['itemID']) && $GLOBALS[$cstRS_POST]['itemID'] !== "";
$hasTokenAlias = isset($GLOBALS[$cstRS_POST]['tokenAlias']);
$hasImmutableRoleFields = isset($GLOBALS[$cstRS_POST]['isMasterTemplate']) || isset($GLOBALS[$cstRS_POST]['parentMasterTokenID']);

if ($RStoken == "" || $clientID <= 0) {
    $response['result'] = "NOK";
    RSReturnArrayResults($response);
}

if ($hasImmutableRoleFields) {
    $response['result'] = "NOK";
    $response['description'] = "TOKEN ROLE AND MASTER RELATIONSHIP ARE IMMUTABLE";
    RSReturnArrayResults($response);
}

$GLOBALS[$cstRS_POST][$cstClientID] = $clientID;

if (!isset($GLOBALS[$cstRS_POST]['RSLogin']) && isset($GLOBALS[$cstRS_POST]['login'])) {
    $GLOBALS[$cstRS_POST]['RSLogin'] = $GLOBALS[$cstRS_POST]['login'];
}

if (!isset($GLOBALS[$cstRS_POST]['RSuserMD5Password']) && isset($GLOBALS[$cstRS_POST]['password'])) {
    $GLOBALS[$cstRS_POST]['RSuserMD5Password'] = $GLOBALS[$cstRS_POST]['password'];
}

if (RSCheckUserAccess() <= 0) {
    $response['result'] = "NOK";
    RSReturnArrayResults($response);
}

if (($hasItemTypeID && !$hasItemID) || (!$hasItemTypeID && $hasItemID)) {
    $response['result'] = "NOK";
    RSReturnArrayResults($response);
}

if (!$hasItemTypeID && !$hasItemID && !$hasTokenAlias) {
    $response['result'] = "OK";
    RSReturnArrayResults($response);
}

$customerItemTypeID = null;
$customerItemID = null;

if ($hasItemTypeID && $hasItemID) {
    $customerItemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
    $customerItemID = $GLOBALS[$cstRS_POST]['itemID'];

    if (!is_numeric($customerItemTypeID) || !is_numeric($customerItemID)) {
        $response['result'] = "NOK";
        RSReturnArrayResults($response);
    }

    $customerItemTypeID = intval($customerItemTypeID);
    $customerItemID = intval($customerItemID);

    if ($customerItemTypeID <= 0 || $customerItemID <= 0 || !verifyItemExists($customerItemID, $customerItemTypeID, $clientID)) {
        $response['result'] = "NOK";
        RSReturnArrayResults($response);
    }
}

$tokenAlias = $hasTokenAlias ? $GLOBALS[$cstRS_POST]['tokenAlias'] : null;
$results = RSeditToken($RStoken, $clientID, $customerItemTypeID, $customerItemID, $tokenAlias);

if (!$results) {
    $response['result'] = "NOK";
} else {
    $response['result'] = "OK";
}

RSReturnArrayResults($response);
