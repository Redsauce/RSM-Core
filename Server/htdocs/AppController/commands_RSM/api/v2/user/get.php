<?php
header('Access-Control-Allow-Origin: *');
//***************************************************************************************
// Description:
//    Gets all matching userID/clientID pairs for a login and password.
// REQUEST BODY (JSON OBJECT):
//
// EXAMPLE:
//     {
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
checkCorrectRequestMethod('GET');

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$login = htmlspecialchars($requestBody->login);
$password = htmlspecialchars($requestBody->password);


$theQuery = "SELECT RS_USER_ID as 'userID', RS_CLIENT_ID as 'clientID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "'";

$result = RSquery($theQuery);

$users = array();
while ($user = mysqli_fetch_assoc($result)) {
  $users[] = array(
    'userID' => $user['userID'],
    'clientID' => $user['clientID']
  );
}

$response = json_encode(array('users' => $users));

returnJsonResponse($response);

function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContains($body, 'login');
  checkBodyContains($body, 'password');
}
