<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$studyID = $GLOBALS['RS_POST']['studyID'];


// get the item types
$groupsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['groups'], $clientID);

// get properties
$groupMainPropertyID = getMainPropertyID($groupsItemTypeID, $clientID);
$groupStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['groupsStudyID'], $clientID);



// get the study groups
$returnProperties = array();
$returnProperties[] = array('ID' => $groupMainPropertyID, 'name' => 'name');

//build an empty filter
$filters = array();
$filters[] = array('ID' => $groupStudyPropertyID, 'value' => $studyID);


$groups = getFilteredItemsIDs($groupsItemTypeID, $clientID, $filters, $returnProperties);

// return results
RSReturnArrayQueryResults($groups);
?>