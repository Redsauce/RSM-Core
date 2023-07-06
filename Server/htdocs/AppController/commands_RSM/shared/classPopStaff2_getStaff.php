<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$onlyActive = $GLOBALS['RS_POST']['onlyActive'];

// get the item type and the main value
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['staff'], $clientID);
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

// get person status property ID
$personStatusPropertyID = getClientPropertyIDRelatedWithByName($definitions['staffStatus'], $clientID);

// retrieve the active status value
$inactiveStatus = getValue(getClientListValueIDRelatedWith(getAppListValueID('staffStatusInactive'), $clientID), $clientID);

// build filter properties array
$filterProperties = array();
if ($onlyActive == '1') {
    $filterProperties[] = array('ID' => $personStatusPropertyID, 'value' => $inactiveStatus, 'mode' => '<>');
}

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');

// get all staff
$staff = iqGetFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

RSreturnQueryResults($staff);
