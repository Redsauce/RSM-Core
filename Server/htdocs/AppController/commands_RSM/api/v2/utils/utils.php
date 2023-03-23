<?php

function prepareValues($propertyValue)
{
  $parsedPropertyValue = str_replace("&amp;", "&", htmlentities($propertyValue, ENT_COMPAT, "UTF-8"));
  return str_replace("'", "&#39;", $parsedPropertyValue);
}

function getRequestBody()
{
  return json_decode(file_get_contents('php://input'));
}
