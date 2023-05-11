<?php

//***************************************************************************************
// Structure
//***************************************************************************************

function checkBodyIsJsonObject($body)
{
  global $RSallowDebug;
  if (!is_object($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be a JSON object '{}'");
    else returnJsonMessage(400, "");
  }
}

function checkIsJsonObjectInArray($body)
{
  global $RSallowDebug;
  if (!is_object($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body array elements must be JSON objects '{}'");
    else returnJsonMessage(400, "");
  }
}

function checkIDsIsArray($body)
{
  global $RSallowDebug;
  if (isset($body->IDs)) {
    if (!is_array($body->IDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'IDs' field must be an array");
      else returnJsonMessage(400, "");
    }
  }
}

function checkPropertyIDsIsArray($body)
{
  global $RSallowDebug;
  if (isset($body->propertyIDs)) {
    if (!is_array($body->propertyIDs)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'propertyIDs' field must be an array");
      else returnJsonMessage(400, "");
    }
  }
}

function checkBodyIsArray($body)
{
  global $RSallowDebug;
  if (!is_array($body)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must be an array '[]'");
    else returnJsonMessage(400, "");
  }
}

function checkIDsIsAnArray($body)
{
  global $RSallowDebug;
  if (!is_array($body->IDs)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body 'IDs' field must be an array '[]'");
    else returnJsonMessage(400, "");
  }
}

//***************************************************************************************
// Contains
//***************************************************************************************

function checkBodyContainsID($body)
{
  global $RSallowDebug;
  if (!(isset($body->ID))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain ID");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsItemTypeId($body)
{
  global $RSallowDebug;
  if (!(isset($body->itemTypeID))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain itemTypeID");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsPropertyID($body)
{
  global $RSallowDebug;
  if (!(isset($body->propertyID))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain propertyID");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsItemTypeIDorPropertyIDs($body)
{
  global $RSallowDebug;
  if (!isset($body->itemTypeID) and !isset($body->propertyIDs)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain at least field 'itemTypeID' or field 'propertyIDs'");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsIDs($body)
{
  global $RSallowDebug;
  if (!isset($body->IDs)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must contain field 'IDs'");
    else returnJsonMessage(400, "");
  }
}

function checkParamsContainsID($body)
{
  global $RSallowDebug;
  if (!(isset($body["ID"]))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request must include query param 'ID'");
    else returnJsonMessage(400, "");
  }
}

function checkParamsContainsPropertyID($body)
{
  global $RSallowDebug;
  if (!(isset($body["propertyID"]))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request must include query param 'propertyID'");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsItemTypeFilterPropertyFilterPropertyID($body)
{
  global $RSallowDebug;
  if (!(isset($body->itemType) && isset($body->filterProperty) && isset($body->filterPropertyID))) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body must include itemType, filterProperty and filterPropertyID");
    else returnJsonMessage(400, "");
  }
}

function checkBodyContainsLogin($body)
{
  global $RSallowDebug;
  if (!isset($body->login)) {
    if ($RSallowDebug) returnJsonMessage(403, "Request body items must contain field 'login'");
    else returnJsonMessage(403, "");
  }
}

function checkBodyContainsPassword($body)
{
  global $RSallowDebug;
  if (!isset($body->password)) {
    if ($RSallowDebug) returnJsonMessage(403, "Request body items must contain field 'password'");
    else returnJsonMessage(403, "");
  }
}

function checkBodyContainsClientID($body)
{
  global $RSallowDebug;
  if (!isset($body->clientID)) {
    if ($RSallowDebug) returnJsonMessage(403, "Request body items must contain field 'clientID'");
    else returnJsonMessage(403, "");
  }
}

//***************************************************************************************
// Types
//***************************************************************************************

function checkItemTypeIDisInteger($body)
{
  global $RSallowDebug;
  if (isset($body->itemTypeID)) {
    if (!is_int($body->itemTypeID)) {
      if ($RSallowDebug) returnJsonMessage(400, "Request body 'itemTypeID' field must be an integer");
      else returnJsonMessage(400, "");
    }
  }
}

function checkIDisInteger($body)
{
  global $RSallowDebug;
  if (!is_int($body->ID)) {
    if ($RSallowDebug) returnJsonMessage(400, "Request body 'ID' field must be an integer");
    else returnJsonMessage(400, "");
  }
}

function checkWParamIsNumeric($body)
{
  global $RSallowDebug;
  if (isset($body["w"]) && !is_numeric($body["w"])) {
    if ($RSallowDebug) returnJsonMessage(400, "w must be a number");
    else returnJsonMessage(400, "");
  }
}

function checkHParamIsNumeric($body)
{
  global $RSallowDebug;
  if (isset($body["h"]) && !is_numeric($body["h"])) {
    if ($RSallowDebug) returnJsonMessage(400, "h must be a number");
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
