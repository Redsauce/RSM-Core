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
      RSerror("checkIsJsonObject: Invalid JSON Object '{}'");
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
      RSerror("checkIsArray: Invalid Array '[]'");
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
      RSerror("checkBodyContains: Request body must contain '{$item}'");
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
      RSerror("checkParamsContains: Request must include query param '{$item}'");
      returnJsonMessage(400, "");
    }
  }
}

function checkBodyContainsAtLeastOne($body, $itemOne, $itemTwo)
{
  global $RSallowDebug;
  if (!(isset($body->$itemOne) || isset($body->$itemTwo))) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "Request body must contain at least field '{$itemOne}' or field '{$itemTwo}'");
    } else {
      RSerror("checkBodyContainsAtLeastOne: Request body must contain at least field '{$itemOne}' or field '{$itemTwo}'");
      returnJsonMessage(400, "");
    }
  }
}

//***************************************************************************************
// Functions to verify and ensure that the request body (api) items are the correct type.
//***************************************************************************************

function checkIsInteger($item)
{
  global $RSallowDebug;
  if (isset($item) && !is_int($item)) {
    if ($RSallowDebug) {
      returnJsonMessage(400, "'{$item}' must be an integer");
    } else {
      RSerror("checkIsInteger: '{$item}' must be an integer");
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
      RSerror("checkADJParamIsValid: adj must be one of those characters: s, f, w, h, d, c");
      returnJsonMessage(400, "");
    }
  }
}
