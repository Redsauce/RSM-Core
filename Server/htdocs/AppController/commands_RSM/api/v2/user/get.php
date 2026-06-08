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


$theQuery = "SELECT rs_users.RS_USER_ID as 'userID', rs_users.RS_CLIENT_ID as 'clientID', rs_clients.RS_NAME as 'clientName', rs_clients.RS_LOGO as 'clientLogo' FROM `rs_users` INNER JOIN `rs_clients` ON rs_clients.RS_ID = rs_users.RS_CLIENT_ID WHERE rs_users.RS_LOGIN = '" . $login . "' AND rs_users.RS_PASSWORD = '" . $password . "'";

$result = RSquery($theQuery);

$users = array();
while ($user = mysqli_fetch_assoc($result)) {
  $users[] = array(
    'userID' => $user['userID'],
    'clientID' => $user['clientID'],
    'clientName' => html_entity_decode($user['clientName']),
    'clientLogo' => bin2hex($user['clientLogo'])
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
