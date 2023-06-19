<?php
// Database connection startup
require_once "../../../utilities/RStools.php";
setAuthorizationTokenOnGlobals();
require_once "../../../utilities/RSdatabase.php";
require_once "../../api_headers.php";
require_once "../../../utilities/RSMverifyBody.php";

checkCorrectRequestMethod("GET");

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$login = $requestBody->login;
$password = $requestBody->password;
$clientID = $requestBody->clientID;

$theQuery = "SELECT RS_ITEM_ID as 'ID' FROM `rs_users` WHERE RS_LOGIN = '" . $login . "' AND RS_PASSWORD = '" . $password . "' AND RS_CLIENT_ID = '" . $clientID . "'";

$result = RSQuery($theQuery);

if ($result->num_rows == 0) {
  if ($RSallowDebug) {
    returnJsonMessage(404, "No users found");
  } else {
    returnJsonMessage(404, "");
  }
}

$ID = mysqli_fetch_assoc($result)["ID"];

$response = array("ID" => $ID);
$response = json_encode($response);

returnJsonResponse($response);

function verifyBodyContent($body)
{
  checkIsJsonObject($body);
  checkBodyContains($body, "login");
  checkBodyContains($body, "password");
  checkBodyContains($body, "clientID");
}
