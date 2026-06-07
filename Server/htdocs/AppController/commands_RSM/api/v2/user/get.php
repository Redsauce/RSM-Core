<?php
//***************************************************************************************
// Description:
//    Gets a user's userID.
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
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';
require_once '../../../utilities/RSMverifyBody.php';

checkCorrectRequestMethod('GET');

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken = isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $GLOBALS[$cstRS_POST][$cstRStoken] : '';
$clientID = getClientID();
$login = sanitizeInput($requestBody->login);
$password = sanitizeInput($requestBody->password);


$theQuery = "SELECT RS_USER_ID as 'ID', RS_ITEM_ID as 'staffItemID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";

$result = RSquery($theQuery);

if ($result->num_rows == 0) {
  if ($RSallowDebug) {
    returnJsonMessage(200, 'No users found');
  } else {
    returnJsonMessage(200, '');
  }
}

$user = mysqli_fetch_assoc($result);
$ID = $user['ID'];

// RS_USER_ID is internal; scope is validated through the linked staff item.
if (!RSstaffItemMatchesTokenCustomerScope($RStoken, $clientID, $user['staffItemID'])) {
  if ($RSallowDebug) {
    returnJsonMessage(403, 'Token customer scope does not allow access to this user');
  } else {
    returnJsonMessage(403, '');
  }
}

$response = json_encode(array('ID' => $ID));

returnJsonResponse($response);

function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContains($body, 'login');
  checkBodyContains($body, 'password');
}
