<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

isset($GLOBALS["RS_POST"]["client"]) ? $clientID = $GLOBALS['RS_POST']['client'] : dieWithError(400);
isset($GLOBALS["RS_POST"]["login"]) ? $login    = $GLOBALS['RS_POST']['login'] : dieWithError(400);
isset($GLOBALS["RS_POST"]["password"]) ? $password = $GLOBALS['RS_POST']['password'] : dieWithError(400);

$theQuery = "SELECT RS_ITEM_ID as 'ID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";

$result = RSquery($theQuery);

// Write back the XML Response to the application without compression
RSreturnQueryResults($result, false);
