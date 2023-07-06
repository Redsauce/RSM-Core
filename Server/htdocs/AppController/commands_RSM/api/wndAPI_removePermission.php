<?php
//****************************************************************************************
//Description:
//    Remove a permission for the specified type and the given token and property
//
//  PARAMETERS:
//  propertyID: ID of the property related to the permission
//       token: token associated to the permission to delete
//  permission: must be one of CREATE, WRITE, READ or DELETE
//****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// First of all, get the ID related to the token
// First of all recover the tokenID pertaining to the passed token
$tokenID =  RSgetTokenID($GLOBALS['RS_POST']['token']);

// And remove the permission to the database
$results = RSremovePermissionFromTokenProperty($tokenID, $GLOBALS['RS_POST']['clientID'], $GLOBALS['RS_POST']['propertyID'], $GLOBALS['RS_POST']['permission']);

$response['result'] = "OK";

RSreturnArrayResults($response);
