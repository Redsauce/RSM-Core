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

if (isset($GLOBALS['RS_GET']['r'])) {
	// The 'r' parameter is used to request data from RSM using GET
	// The idea is to encrypt the request so the user can't alter it to get more data or other files
	// So instead of (for example) imageID=5 in the URL, we send a single encrypter r parameter
	// That can be decrypted here, replacing the Global GET variables
	$encryptedData = pack("H*", $GLOBALS['RS_GET']['r']);
	$decryptedData = openssl_decrypt($encryptedData, 'bf-ecb', $RSblowfishKey, OPENSSL_RAW_DATA);
	$parameters    = explode("&", rtrim($decryptedData, "\x05"));

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
		if ($GLOBALS['RS_POST']['clientID'] != RSClientFromToken($GLOBALS['RS_POST']['RStoken'])) RSReturnError("ACCESS DENIED", -3);

	} else {
		// We don't have a token so validate user permissions
		if (RSCheckCompatibleDB(0) == 0) RSReturnError("INCOMPATIBLE VERSION", -4);
		if ($RSuserID == 0) RSReturnError("ACCESS DENIED", -5);
	}

} elseif (isset($GLOBALS['RS_POST']['RStoken'])) {
	// If we don't have a clientID, validates if there is a valid token sent through POST
	$GLOBALS['RS_POST']['clientID'] = RSClientFromToken($GLOBALS['RS_POST']['RStoken']);
	if ($GLOBALS['RS_POST']['clientID'] <= 0) RSReturnError("ACCESS DENIED", -6);

} elseif (isset($GLOBALS['RS_GET']['RStoken'])) {
    // If we don't have a clientID, validates if there is a valid token sent through GET
    $GLOBALS['RS_POST']['clientID'] = RSClientFromToken($GLOBALS['RS_GET']['RStoken']);
    if ($GLOBALS['RS_POST']['clientID'] <= 0) RSReturnError("ACCESS DENIED", -7);

} else {
	// By default we check if the database is compatible
	if (RSCheckCompatibleDB(0) == 0) RSReturnError("INCOMPATIBLE VERSION", -8);

}
?>
