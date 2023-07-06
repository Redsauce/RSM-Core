<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RStools.php";

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["login"]) ? $login    = $GLOBALS["RS_POST"]["login"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["password"]) ? $password = $GLOBALS["RS_POST"]["password"] : dieWithError(400);

if (empty($clientID)) {
  $theQuery = "SELECT
    
          rs_users.RS_USER_ID as 'userID',
        rs_clients.RS_ID      as 'clientID',
        rs_clients.RS_NAME    as 'clientName'

        FROM rs_users INNER JOIN rs_clients
        ON rs_users.RS_CLIENT_ID = rs_clients.RS_ID

        WHERE

        RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "'";
} else {
  $theQuery = "SELECT RS_USER_ID as 'ID' FROM rs_users WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";
}

// Write back the XML Response
RSreturnQueryResults(RSquery($theQuery));
