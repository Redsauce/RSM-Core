<?php
//***************************************************
//Description:
//	Get client custom itemType name related with received appItemTypeName
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$appItemTypeName = $GLOBALS[$cstRS_POST]['appItemTypeName'];

// get client itemTypeID related with received appItemTypeName
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions[$appItemTypeName], $clientID);

// get itemtype name
$results['customItemTypeName'] = getClientItemTypeName($itemTypeID, $clientID);

// And return XML results
RSReturnArrayResults($results);
