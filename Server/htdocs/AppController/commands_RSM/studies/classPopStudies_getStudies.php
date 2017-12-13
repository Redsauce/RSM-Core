<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['studies'], $clientID);
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');

//build an empty filter
$filters = array();

$studies = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);


RSReturnArrayQueryResults($studies);

?>