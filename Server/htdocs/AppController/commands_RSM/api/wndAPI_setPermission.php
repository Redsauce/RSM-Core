<?php
//****************************************************************************************
//Description:
//    Creates a permission of the specified type for the given token and property
//  
//  PARAMETERS:
//  propertyID: ID of the property related to the permission
//       token: token to associate the permission to
//  permission: must be one of CREATE, WRITE, READ or DELETE
//****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// First of all, get the ID related to the token
// First of all recover the tokenID pertaining to the passed token
$tokenID = RSgetTokenID($GLOBALS['RS_POST']['token']);

// There is no need of checking if the permission has been already inserted
// because the database structure avoids duplicaded permissions

// And add the permission to the database
$results = RScreateTokenPermission($tokenID,$GLOBALS['RS_POST']['clientID'],$GLOBALS['RS_POST']['propertyID'],$GLOBALS['RS_POST']['permission']);
                                                    
$response['result'] = "OK";

RSReturnArrayResults($response);
?>