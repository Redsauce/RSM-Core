<?php
// A simply function to check the presence of an element into an array...
function searchInArray($array, $key, $value)
{
  for ($i = 0; $i < count($array); $i++) {
    if ($array[$i][$key] == $value) {
      return true;
    }
  }

  return false;
}

// Database connection startup
require_once "../utilities/RSdatabase.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// prepare arrays
$update_vars = array();
$insert_vars = array();
$delete_vars = array();

for ($i = 0; isset($GLOBALS['RS_POST']['var' . $i]); $i++) {
  $varArr = explode(' ', $GLOBALS['RS_POST']['var' . $i]);

  switch ($varArr[0]) {
    case '1':
      // variable to update
      $update_vars[] = array('dbName' => base64_decode($varArr[1]), 'name' => base64_decode($varArr[2]), 'value' => base64_decode($varArr[3]), 'type' => $varArr[4]);
      break;
    case '2':
      // variable to insert
      $insert_vars[] = array('name' => base64_decode($varArr[1]), 'value' => base64_decode($varArr[2]), 'type' => $varArr[3]);
      break;
    case '3':
      // variable to delete
      $delete_vars[] = array('dbName' => base64_decode($varArr[1]));
      break;
    default:
      break;
  }
}



// --- CHECK CONFLICTS ---
$vars = array();

foreach ($update_vars as $var) {
  $vars[] = $var['name'];
}
foreach ($insert_vars as $var) {
  $vars[] = $var['name'];
}


if (!empty($vars)) {
  // check that the variables we have to insert and/or update do not exist into the database
  $theQuery = 'SELECT RS_NAME FROM rs_globals WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_NAME IN ("' . implode('","', $vars) . '")';

  // execute query
  $checkResults = RSQuery($theQuery);

  if ($checkResults && $checkResults->num_rows > 0) {
    // one or more variables already exist... check if these variables must be deleted or renamed, in which case the update operation is still valid
    while ($row = $checkResults->fetch_assoc()) {
      if ((!searchInArray($update_vars, 'dbName', $row['RS_NAME'])) && (!searchInArray($delete_vars, 'dbName', $row['RS_NAME']))) {
        // return NOK and the variable that causes the conflict
        $results['result'] = 'NOK';
        $results['var'] = $row['RS_NAME'];

        // Write XML Response back to the application
        RSReturnArrayResults($results);
        exit;
      }
    }
  }
}

// --- CONFLICTS CHECK OK ---
// --- DELETE OLD ---
$vars = array();

foreach ($update_vars as $var) {
  $vars[] = $var['dbName'];
}
foreach ($delete_vars as $var) {
  $vars[] = $var['dbName'];
}

if (!empty($vars)) {
  // remove old variables from database
  $theQuery = 'DELETE FROM rs_globals WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_NAME IN ("' . implode('","', $vars) . '")';

  // execute query
  $queryResult = RSQuery($theQuery);
}




// --- INSERT NEW ---
$vars = array();

foreach ($update_vars as $var) {
  $vars[] = $var;
}
foreach ($insert_vars as $var) {
  $vars[] = $var;
}

if (!empty($vars)) {
  // insert new variables into the database
  $theQuery = 'INSERT INTO rs_globals (RS_CLIENT_ID, RS_NAME, RS_VALUE, RS_IMAGE) VALUES ';

  for ($i = 0; $i < count($vars); $i++) {
    if ($vars[$i]['type'] == '0') {
      $theQuery .= '(' . $clientID . ',"' . $vars[$i]['name'] . '","' . $vars[$i]['value'] . '",' . $vars[$i]['type'] . '),';
    } else {
      $theQuery .= '(' . $clientID . ',"' . $vars[$i]['name'] . '",0x' . $vars[$i]['value'] . ',' . $vars[$i]['type'] . '),';
    }
  }

  // remove last comma
  $theQuery = substr($theQuery, 0, -1);

  // execute query
  $queryResult = RSQuery($theQuery);

  if (!$queryResult) {
    // return NOK
    $results['result'] = 'NOK';
    $results['message'] = $mysqli->error;

    // And write XML Response back to the application
    RSReturnArrayResults($results);
    exit;
  }
}



// return OK
$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);
