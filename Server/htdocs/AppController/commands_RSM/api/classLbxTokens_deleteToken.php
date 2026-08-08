<?php
// ***************************************************************************************
// DESCRIPTION
//     Deletes the token identified for a specified clientID
//
// PARAMETERS
//      token: the string pertaining to the token to delete
//   clientID: ID pertaining to the client that holds the token
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// First of all we must retrieve the ID pertaining to the token
$clientID = RSrequireTokenManagementAccess();
$token = isset($GLOBALS[$cstRS_POST]['token']) ? $GLOBALS[$cstRS_POST]['token'] : '';
RSrequireTokenOwnedByClient($token, $clientID);
$results = RSdeleteTokenSafely($token, $clientID);
$response['result'] = $results ? "OK" : "NOK";
if (!$results) $response['description'] = "MASTER TOKEN HAS CHILD TOKENS OR COULD NOT BE DELETED";

// And write XML Response back to the application
RSReturnArrayResults($response);
