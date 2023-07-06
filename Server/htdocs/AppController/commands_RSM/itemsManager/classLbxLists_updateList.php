<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] > 0) {

  //We check if the user already exists
  $theQuery_userExists = "SELECT RS_LIST_ID FROM rs_lists WHERE RS_LIST_ID='" . $GLOBALS['RS_POST']['listID'] . "' AND RS_CLIENT_ID=" . $GLOBALS['RS_POST']['clientID'];
  $resultUsers = RSquery($theQuery_userExists);

  $errorMessage = "ERROR WHILE UPDATING ITEMTYPE";
  if ($resultUsers->fetch_array() != 0) {
    // The list exists, so we update the user
    $theQuery = "UPDATE rs_lists SET RS_NAME = '" . base64_decode($GLOBALS['RS_POST']['name']) . "' WHERE RS_LIST_ID=" . $GLOBALS['RS_POST']['listID'] . " AND RS_CLIENT_ID=" . $GLOBALS['RS_POST']['clientID'];

    //show query if debug mode
    if (isset($GLOBALS['RS_POST']['RSdebug']) && $GLOBALS['RS_POST']['RSdebug']) {
      echo $theQuery;
    }

    if ($result = RSquery($theQuery)) {
      $results['result'] = "OK";
      $results['ID'] = $GLOBALS['RS_POST']['listID'];
      $results['name'] = base64_decode($GLOBALS['RS_POST']['name']);
    } else {
      RSreturnError($errorMessage, "15");
    }
  } else {
    RSreturnError($errorMessage, "15");
  }
} else {
  RSreturnError($errorMessage, "15");
}
// And write XML Response back to the application
RSreturnArrayResults($results);
