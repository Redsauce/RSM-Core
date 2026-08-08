<?php
//***************************************************
//RSsecurityCheck.php
//***************************************************
//Description:
//	checks if the application version is compatible with
//  the database in use, and also checks if the user
//  has privileges to work with the system
//***************************************************
//Version:
//	v1.0: checks if the application is compatible with
//        the database and then validates passed login
//        and password against DB
//  v1.1: It uses the rs_users table for all the apps
//***************************************************
//Input: POST
//	         RSuserID: user's login
//	RSuserMD5Password: user's password encrypted in MD5
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

$RSuserID =  0; // By default there is not a defined user

if (isset($GLOBALS[$cstRS_GET]['r'])) {
	// The 'r' parameter is used to request data from RSM using GET
	// The idea is to encrypt the request so the user can't alter it to get more data or other files
	// So instead of (for example) imageID=5 in the URL, we send a single encrypter r parameter
	// That can be decrypted here, replacing the Global GET variables
	$encryptedData = pack("H*", $GLOBALS[$cstRS_GET]['r']);
	$decryptedData = openssl_decrypt($encryptedData, 'bf-ecb', $RSblowfishKey, OPENSSL_RAW_DATA);
	$parameters    = explode("&", rtrim($decryptedData, "\x05"));

	foreach ($parameters as $parameter) {
		$parameter = explode("=", $parameter);
		$GLOBALS[$cstRS_GET][$parameter[0]] = $parameter[1];
	}

	unset($GLOBALS[$cstRS_GET]['r']);
}

// If a clientID is given...
if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
	// and a token is given too...
	$RSuserID = RSCheckUserAccess();

	if (isset($GLOBALS[$cstRS_POST][$cstRStoken])) {
		if (!RSisTokenEnabled($GLOBALS[$cstRS_POST][$cstRStoken])) RSReturnError("ACCESS DENIED", -3);
		// validates if their associated clients match.
		if ($GLOBALS[$cstRS_POST][$cstClientID] != RSClientFromToken($GLOBALS[$cstRS_POST][$cstRStoken])) RSReturnError("ACCESS DENIED", -3);

	} else {
		// We don't have a token so validate user permissions
		if (RSCheckCompatibleDB(0) == 0) RSReturnError("INCOMPATIBLE VERSION", -4);
		if ($RSuserID <= 0) RSReturnError("ACCESS DENIED", -5);
	}

} elseif (isset($GLOBALS[$cstRS_POST][$cstRStoken])) {
	// If we don't have a clientID, validates if there is a valid token sent through POST
	if (!RSisTokenEnabled($GLOBALS[$cstRS_POST][$cstRStoken])) RSReturnError("ACCESS DENIED", -6);
	$GLOBALS[$cstRS_POST][$cstClientID] = RSClientFromToken($GLOBALS[$cstRS_POST][$cstRStoken]);
	if ($GLOBALS[$cstRS_POST][$cstClientID] <= 0) RSReturnError("ACCESS DENIED", -6);

} elseif (isset($GLOBALS[$cstRS_GET][$cstRStoken])) {
	// If we don't have a clientID, validates if there is a valid token sent through GET
	if (!RSisTokenEnabled($GLOBALS[$cstRS_GET][$cstRStoken])) RSReturnError("ACCESS DENIED", -7);
	$GLOBALS[$cstRS_POST][$cstClientID] = RSClientFromToken($GLOBALS[$cstRS_GET][$cstRStoken]);
	if ($GLOBALS[$cstRS_POST][$cstClientID] <= 0) RSReturnError("ACCESS DENIED", -7);

} else {
	// By default we check if the database is compatible
	if (RSCheckCompatibleDB(0) == 0) RSReturnError("INCOMPATIBLE VERSION", -8);

}
