<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$onlyOpen = $GLOBALS['RS_POST']['onlyOpen'];

// get the item type and the main value
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['projects'], $clientID);


// build filter properties array
$filterProperties = array();

if ($onlyOpen == '1') {
    // retrieve the open status
    $openStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('projectStatusOpen'), $clientID), $clientID);

    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['projectStatus'], $clientID), 'value' => $openStatus);
}

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getMainPropertyID($itemTypeID, $clientID), 'name' => 'mainValue');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['projectStaff'], $clientID), 'name' => 'staff');


// get projects list
$projectsList = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');


// And return XML response back to the application
RSreturnQueryResults($projectsList);
