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
  $json="";
  if ($message!="") $json = '{"message": "' . $message . '"}';
  header('Content-Type: application/json', true, $code);
  Header("Content-Length: " . strlen($json));
  echo $json;
  die();
}
