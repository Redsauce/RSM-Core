<?php

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0) {
  $theQuery = "INSERT INTO rs_categories (RS_CATEGORY_ID, RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_NAME, RS_ORDER) VALUES (" . getNextIdentification('rs_categories', 'RS_CATEGORY_ID', $GLOBALS['RS_POST']['clientID']) . ",'" . $GLOBALS['RS_POST']['clientID'] . "','" . $GLOBALS['RS_POST']['itemtypeID'] . "', '" . base64_decode($GLOBALS['RS_POST']['name']) . "', " . getGenericNext('rs_categories', 'RS_ORDER', array("RS_CLIENT_ID" => $GLOBALS['RS_POST']['clientID'])) . ")";

  $result = RSQuery($theQuery);
  $results['result'] = "OK";
  $results['categoryID'] = getLastIdentification('rs_categories', 'RS_CATEGORY_ID', $GLOBALS['RS_POST']['clientID']);
  $results['name'] = base64_decode($GLOBALS['RS_POST']['name']);
} else {
  $results['result'] = "NOK";
}


// And write XML Response back to the application
RSReturnArrayResults($results);
