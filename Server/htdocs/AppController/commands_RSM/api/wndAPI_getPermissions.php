<?php
//****************************************************************************************
// DESCRIPTION
//     Retrieves the list of permissions for the given token and itemtypeID
//
// PARAMETERS:
//        token: token for which the permissions must be retrieved
//   itemTypeID: ID of the item needed to recover the permissions
//
// RETURNS:
//   Array of permissions. Each position corresponds to an entry of the following:
//     permission: Can be CREATE, READ, WRITE or DELETE
//****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// First of all recover the tokenID pertaining to the passed token
$tokenID = RSgetTokenID($GLOBALS['RS_POST']['token']);

// Now we build the query
$theQuery = "SELECT RS_PERMISSION AS  'permission', RS_PROPERTY_ID as 'propertyID' 
                   FROM rs_token_permissions
                  WHERE RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "' AND (1 ";

// Now we have to get the propertyIDs pertaining to the passed item type ID
$propertyIDs = getUserVisiblePropertiesIDs($GLOBALS['RS_POST']['itemTypeID'], $GLOBALS['RS_POST']['clientID'], $RSuserID);

foreach ($propertyIDs as $propertyID) $theQuery = $theQuery . " OR RS_PROPERTY_ID = " . $propertyID;

$theQuery = $theQuery . ") AND  RS_TOKEN_ID = " . $tokenID;

$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
?>