<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$personID = $GLOBALS['RS_POST']['personID'];
$onlyOpen = $GLOBALS['RS_POST']['onlyOpen'];

// Get the item type and the main value
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['projects'], $clientID);

// get staff property ID
$staffPropertyID = getClientPropertyIDRelatedWithByName($definitions['projectStaff'], $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $staffPropertyID, 'value' => $personID, 'mode' => 'IN');

if ($onlyOpen == '1') {
    // retrieve the open status
    $openStatus = getValue(getClientListValueIDRelatedWith(getAppListValueID('projectStatusOpen'), $clientID), $clientID);

    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['projectStatus'], $clientID), 'value' => $openStatus);
}

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getMainPropertyID($itemTypeID, $clientID), 'name' => 'mainValue');
$returnProperties[] = array('ID' => $staffPropertyID, 'name' => 'staff');

// get projects list
$projectsList = iqGetFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');


// And return XML response back to the application
RSreturnQueryResults($projectsList);
