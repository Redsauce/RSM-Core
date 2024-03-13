<?php

//*******************************************************************************************
// Functions to verify and ensure the accuracy of the request body (api) and its contents
//*******************************************************************************************

function checkIsJsonObject($item)
{
  global $RSallowDebug;
  if (!is_object($item)) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Invalid JSON Object '{}'");
    } else {
      RSError("checkIsJsonObject: Invalid JSON Object '{}'");
      returnJsonMessage(400, "");
    }
  }
}

function checkIsArray($item)
{
  global $RSallowDebug;
  if (!is_null($item) && !is_array($item)) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Invalid Array '[]'");
    } else {
      RSError("checkIsArray: Invalid Array '[]'");
      returnJsonMessage(400, "");
    }
  }
}

//*****************************************************************************************
// Functions to verify and ensure that the request body (api) includes the mandatory items
//*****************************************************************************************

function checkBodyContains($body, $item)
{
  global $RSallowDebug;
  if (!isset($body->$item)) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Request body must contain '{$item}'");
    } else {
      RSError("checkBodyContains: Request body must contain '{$item}'");
      returnJsonMessage(400, "");
    }
  }
}

function checkParamsContains($params, $item)
{
  global $RSallowDebug;
  if (!isset($params[$item])) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Request must include query param '{$item}'");
    } else {
      RSError("checkParamsContains: Request must include query param '{$item}'");
      returnJsonMessage(400, "");
    }
  }
}

function checkBodyContainsAtLeastOne($body, $item1, $item2)
{
  global $RSallowDebug;
  if (!(isset($body->$item1) || isset($body->$item2))) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Request body must contain at least field '{$item1}' or field '{$item2}'");
    } else {
      RSError("checkBodyContainsAtLeastOne: Request body must contain at least field '{$item1}' or field '{$item2}'");
      returnJsonMessage(400, "");
    }
  }
}

//***************************************************************************************
// Functions to verify and ensure that the request body (api) items are the correct type.
//***************************************************************************************

function checkStringIsInteger($item)
{
  global $RSallowDebug;
  if (isset($item) && !is_numeric($item)) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "'{$item}' string must be an integer");
    } else {
      RSError("checkStringIsInteger: '{$item}' string must be an integer");
      returnJsonMessage(400, "");
    }
  }
}

function checkADJParamIsValid($body)
{
  global $RSallowDebug;
  if (isset($body["adj"]) && !preg_match('/^[sfwhdc]$/', $body["adj"])) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "adj must be one of those characters: s, f, w, h, d, c");
    } else {
      RSError("checkADJParamIsValid: adj must be one of those characters: s, f, w, h, d, c");
      returnJsonMessage(400, "");
    }
  }
}
