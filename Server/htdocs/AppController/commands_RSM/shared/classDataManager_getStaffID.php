<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RStools.php";

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["login"   ]) ? $login    = $GLOBALS["RS_POST"]["login"   ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["password"]) ? $password = $GLOBALS["RS_POST"]['password'] : $password = "";

if ($password === "") {
    // Verification with a badge
    $theQuery = "SELECT RS_ITEM_ID as 'ID' FROM `rs_users` WHERE RS_BADGE = '" . $login . "' AND RS_CLIENT_ID = '" . $clientID . "'";

} else {
    // Verification with a username and password.
    $theQuery = "SELECT RS_ITEM_ID as 'ID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";  

}

// Write back the XML Response to the application without compression
RSReturnQueryResults(RSQuery($theQuery));

?>
