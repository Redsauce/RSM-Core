<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RStools.php";
require_once "../utilities/RSMitemsManagement.php";

header('Access-Control-Allow-Origin: *');

$RSallowUncompressed = true;

isset($GLOBALS[$cstRS_POST]["client"  ]) ? $clientID = $GLOBALS[$cstRS_POST]['client'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["login"   ]) ? $login    = $GLOBALS[$cstRS_POST]['login'   ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["password"]) ? $password = $GLOBALS[$cstRS_POST]['password'] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken = "";

$theQuery = "SELECT RS_USER_ID as 'ID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";
$theQueryValidation = "SELECT RS_USER_ID as 'ID', RS_ITEM_ID as 'staffItemID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";

$result = RSQuery($theQueryValidation);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!RSstaffItemMatchesTokenCustomerScope($RStoken, $clientID, $row['staffItemID'])) {
        dieWithError(403);
    }
}

$result = RSQuery($theQuery);

// Write back the XML Response to the application without compression
RSReturnQueryResults($result, false);
