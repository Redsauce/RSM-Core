<?php
//***************************************************
//Description:
//  Get client custom property name related with received appPropertyName
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$appPropertyName = $GLOBALS['RS_POST']['appItemPropertyName'];

// get client property name related with received appPropertyName
$results['customItemPropertyName'] = getClientPropertyName_RelatedWith_byName($definitions[$appPropertyName], $clientID);

// And return XML results
RSReturnArrayResults($results);
