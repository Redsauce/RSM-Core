<?php
//***************************************************************************************
// Description:
//    Gets a user's staffID.
// REQUEST BODY (JSON OBJECT):
//
// EXAMPLE:
//     {
//         "login": "correo@gmail.com",
//         "password": "12345",
//     }
//
//***************************************************************************************

// Database connection startup
require_once '../../../utilities/RStools.php';
setAuthorizationTokenOnGlobals();
require_once '../../../utilities/RSdatabase.php';
require_once '../../api_headers.php';
require_once '../../../utilities/RSMverifyBody.php';

checkCorrectRequestMethod('GET');

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$clientID = getClientID();
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
  checkBodyContains($body, 'login');
  checkBodyContains($body, 'password');
}
