<?php
//***********************************************************
//Description:
//  marks an invoice as sent, adding the today's date
// --> updated for the v.3.10
//***********************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$sendDate = $GLOBALS['RS_POST']['sendDate'];

// get operations item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);

// save the domicily date
setItemPropertyValue($definitions['operationSendDate'], $itemTypeID, $operationID, $clientID, $sendDate, $RSuserID);


$results['sendDate'] = getPropertyValue($definitions['operationSendDate'], $itemTypeID, $operationID, $clientID);

// And write XML Response back to the application
RSreturnArrayResults($results);
