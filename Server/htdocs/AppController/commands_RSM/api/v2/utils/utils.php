<?php

//***************************************************//
// utils.php
//***************************************************//
// Description:
//	diverse utility functions
//***************************************************//

// Set the Authorization token readed on the header and puts it in the $GLOBALS variable 
function setAuthorizationTokenOnGlobals()
{
  //We need this variable to exists in order to make RSdatabase work propertly.
  $GLOBALS['RS_POST']['RStoken'] = getallheaders()["Authorization"];
  if (strpos($GLOBALS['RS_POST']['RStoken'], "Basic ") !== false) {
    $GLOBALS['RS_POST']['RStoken'] = str_replace("Basic ", "", $GLOBALS['RS_POST']['RStoken']);
    $GLOBALS['RS_POST']['RStoken'] =  base64_decode($GLOBALS['RS_POST']['RStoken']);
    $GLOBALS['RS_POST']['RStoken'] = str_replace("Authorization:", "", $GLOBALS['RS_POST']['RStoken']);
    $GLOBALS['RS_POST']['RStoken'] = str_replace(";", "", $GLOBALS['RS_POST']['RStoken']);
  }
}

// Replace incorrect values related to UTF-8
function replaceUtf8Characters($propertyValue)
{
  $parsedPropertyValue = str_replace("&amp;", "&", htmlentities($propertyValue, ENT_COMPAT, "UTF-8"));
  return str_replace("'", "&#39;", $parsedPropertyValue);
}
// Returns request body sent through petition, transormed into php object (json)
function getRequestBody()
{
  return json_decode(file_get_contents('php://input'));
}

function returnJsonMessage($code, $message)
{
  $json = "";
  if ($message != "") $json = '{"message": "' . $message . '"}';
  header('Content-Type: application/json', true, $code);
  Header("Content-Length: " . strlen($json));
  echo $json;
  die();
}

//Gets the clientID from $GLOBALS. Returns an error if it's not found.
function getClientID()
{
  global $RSallowDebug;

  if (isset($GLOBALS['RS_POST']['clientID'])) return $GLOBALS['RS_POST']['clientID'];
  else {
    if ($RSallowDebug) returnJsonMessage(400, "clientID could not be retrieved");
    else returnJsonMessage(400, "");
  }
}

//Gets the RStoken from $GLOBALS. Returns an error if it's not found.
function getRStoken()
{
  global $RSallowDebug;

  if (isset($GLOBALS['RS_POST']['RStoken'])) return $GLOBALS['RS_POST']['RStoken'];
  else {
    if ($RSallowDebug) returnJsonMessage(400, "RStoken could not be retrieved");
    else returnJsonMessage(400, "");
  }
}

//Gets the RSuserID from $GLOBALS. Returns an error if it's not found.
function getRSuserID()
{
  global $RSallowDebug;

  if (isset($GLOBALS['RSuserID'])) return $GLOBALS['RSuserID'];
  else {
    if ($RSallowDebug) returnJsonMessage(400, "RSuserID could not be retrieved");
    else returnJsonMessage(400, "");
  }
}
// returns api response in json
function returnJsonResponse($response)
{
    header('Content-Type: application/json', true, 200);
    Header("Content-Length: " . strlen($response));
    echo $response;
    die();
}