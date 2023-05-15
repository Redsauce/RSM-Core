<?php

//***************************************************************************************
// Structure
//***************************************************************************************

function checkIsJsonObject($item)
{
  global $RSallowDebug;
  if (!is_object($item)) {
    if ($RSallowDebug) returnJsonMessage(400, "Invalid JSON Object '{}'");
    else returnJsonMessage(400, "");
  }
}

function checkIsArray($item)
{
  global $RSallowDebug;
  if (!is_array($item)) {
    if ($RSallowDebug) returnJsonMessage(400, "Invalid Array '[]'");
    else returnJsonMessage(400, "");
  }
}

//***************************************************************************************
// Contains
//***************************************************************************************

function checkBodyContains($body, $item)
{
  global $RSallowDebug;
  if (!isset($body->$item)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain '{$item}'");
    else returnJsonMessage(400, "");
  }
}

function checkParamsContains($params, $item)
{
  global $RSallowDebug;
  if (!isset($params[$item])) {
    if ($RSallowDebug) returnJsonMessage(400, "Request must include query param '{$item}'");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsAtLeastOne($body, $item1, $item2)
{
  global $RSallowDebug;
  if (!(isset($body->$item1) or isset($body->$item2))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain at least field '{$item1}' or field '{$item2}'");
    else returnJsonMessage(400, "");
  }
}

//***************************************************************************************
// Types
//***************************************************************************************

function checkIsInteger($item)
{
  global $RSallowDebug;
  if (!is_int($item)) {
    if ($RSallowDebug) returnJsonMessage(400, "'{$item}' must be an integer");
    else returnJsonMessage(400, "");
  }
}

function checkADJParamIsValid($body)
{
  global $RSallowDebug;
  if (isset($body["adj"]) && !preg_match('/^[sfwhdc]$/', $body["adj"])) {
    if ($RSallowDebug) returnJsonMessage(400, "adj must be one of those characters: s, f, w, h, d, c");
    else returnJsonMessage(400, "");
  }
}
