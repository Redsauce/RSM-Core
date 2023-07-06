<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$user      = $GLOBALS['RS_POST']['userID'];
$startDate = $GLOBALS['RS_POST']['startDate'];
$endDate   = $GLOBALS['RS_POST']['endDate'];
$clientID  = $GLOBALS['RS_POST']['clientID'];


// get worksessions item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);

// get properties
$wsUserPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionUser'], $clientID);
$wsStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionStartDate'], $clientID);
$wsDurationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionDuration'], $clientID);
$wsTaskPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'], $clientID);
$wsDescriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionDescription'], $clientID);


// build filter properties
$filterProperties = array();
$filterProperties[] = array('ID' => $wsUserPropertyID, 'value' => $user);
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $startDate, 'mode' => 'SAME_OR_AFTER');
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $endDate, 'mode' => 'BEFORE');

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $wsStartDatePropertyID, 'name' => 'date');
$returnProperties[] = array('ID' => $wsDurationPropertyID, 'name' => 'hours');
$returnProperties[] = array('ID' => $wsTaskPropertyID, 'name' => 'task', 'trName' => 'name');
$returnProperties[] = array('ID' => $wsDescriptionPropertyID, 'name' => 'comments');

// get worksessions
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);


// And write XML Response back to the application
RSreturnArrayQueryResults($results);
