<?php
//***************************************************************************************
// Description:
//    Gets a user's staffID.
// REQUEST BODY (JSON OBJECT):
//
// EXAMPLE:
//     {
//         "clientID": "1",
//         "login": "correo@gmail.com",
//         "password": "827ccb0eea8a706c4c34a16891f84e7b",
//     }
//
// NOTE:
//     The password must be sent as an MD5 hash, matching the value stored in the DB.
//
//***************************************************************************************

// Database connection startup
require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';

header('Access-Control-Allow-Origin: *');

checkCorrectRequestMethod('GET');

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$clientID = sanitizeInput($requestBody->clientID);
$login = sanitizeInput($requestBody->login);
$password = sanitizeInput($requestBody->password);

$theQuery = "SELECT RS_ITEM_ID as 'ID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";

$result = RSquery($theQuery);

if ($result->num_rows == 0) {
  if ($RSallowDebug) {
    returnJsonMessage(200, 'No user found');
  } else {
    returnJsonMessage(200, '');
  }
}

$ID = mysqli_fetch_assoc($result)['ID'];

$response = json_encode(array('ID' => $ID));

returnJsonResponse($response);

function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContains($body, 'clientID');
  checkBodyContains($body, 'login');
  checkBodyContains($body, 'password');
}
