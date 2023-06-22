<?php
//***************************************************
//RSsecurityCheck.php
//***************************************************
//Description:
//  checks if the application version is compatible with
//  the database in use, and also checks if the user
//  has privileges to work with the system
//***************************************************
//Version:
//  v1.0: checks if the application is compatible with
//        the database and then validates passed login
//        and password against DB
//  v1.1: It uses the rs_users table for all the apps
//***************************************************
//Input: POST
//           RSuserID: user's login
//  RSuserMD5Password: user's password encrypted in MD5
//            RSbuild: Application build
//          RSappName: Application Name
//         RSlanguage: Application language
//***************************************************
//Output: RSRecordset XML and error code with message
// If the application version is not registered against the database send VERSION ERROR
// If the current user has not access to work with the selected database (if the database is set) send ACCESS ERROR
//***************************************************

require_once "RSvalidationFunctions.php";
require_once "RSMtokensManagement.php";

define('ACCESS_DENIED_MSG', 'ACCESS DENIED');

$RSuserID =  0; // By default there is not a defined user

if (isset($GLOBALS['RS_GET']['r'])) {
    $parameters = explode("&", rtrim(mcrypt_decrypt("blowfish", $RSblowfishKey, pack("H*", $GLOBALS['RS_GET']['r']), "ecb"), "\x05"));

    foreach ($parameters as $parameter) {
        $parameter = explode("=", $parameter);
        $GLOBALS['RS_GET'][$parameter[0]] = $parameter[1];
    }
    unset($GLOBALS['RS_GET']['r']);
}

// If a clientID is given...
if (isset($GLOBALS['RS_POST']['clientID'])) {
    // and a token is given too...
    $RSuserID = RSCheckUserAccess();

    if (isset($GLOBALS['RS_POST']['RStoken'])) {
        // validates if their associated clients match.
        if ($GLOBALS['RS_POST']['clientID'] != RSClientFromToken($GLOBALS['RS_POST']['RStoken'])) {
            RSReturnError(ACCESS_DENIED_MSG, -3);
        }
    } else {
        // We don't have a token so validate user permissions
        if (RSCheckCompatibleDB(0) == 0) {
            RSReturnError("INCOMPATIBLE VERSION", -4);
        }
        if ($RSuserID == 0) {
            RSReturnError(ACCESS_DENIED_MSG, -3);
        }
    }

} elseif (isset($GLOBALS['RS_POST']['RStoken'])) {
    // If we don't have a clientID, validates if there is a valid token sent through POST
    $GLOBALS['RS_POST']['clientID'] = RSClientFromToken($GLOBALS['RS_POST']['RStoken']);
    if ($GLOBALS['RS_POST']['clientID'] <= 0) {
        RSReturnError(ACCESS_DENIED_MSG, -3);
    }

} elseif (isset($GLOBALS['RS_GET']['RStoken'])) {
    // If we don't have a clientID, validates if there is a valid token sent through GET
    $GLOBALS['RS_POST']['clientID'] = RSClientFromToken($GLOBALS['RS_GET']['RStoken']);
    if ($GLOBALS['RS_POST']['clientID'] <= 0) {
        RSReturnError(ACCESS_DENIED_MSG, -3);
    }

} else {
    // By default we check if the database is compatible
    if (RSCheckCompatibleDB(0) == 0) {
        RSReturnError("INCOMPATIBLE VERSION", -4);
    }
}
