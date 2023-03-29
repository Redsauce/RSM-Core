<?php

//***************************************************//
// utils.php
//***************************************************//
// Description:
//	diverse utility functions
//***************************************************//

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
