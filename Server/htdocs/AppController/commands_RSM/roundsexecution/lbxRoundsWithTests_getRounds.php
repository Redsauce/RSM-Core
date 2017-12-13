<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$studyID =  $GLOBALS['RS_POST']['studyID'];

// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);

$namePropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningName'], $clientID);
$orderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningOrder'], $clientID);
$testCasesListPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedTestCasesIDs'], $clientID);
$roundAssociatedStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedStudyID'], $clientID);


// build filter properties array
$filters = array();
$filters[] = array('ID' => $roundAssociatedStudyPropertyID, 'value' => $studyID);
$filters[] = array('ID' => $testCasesListPropertyID, 'value' => '', 'mode' => '<>');

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $namePropertyID, 'name' => 'name');
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');
$returnProperties[] = array('ID' => $testCasesListPropertyID, 'name' => 'testCasesList');
$returnProperties[] = array('ID' => $roundAssociatedStudyPropertyID, 'name' => 'studyID');


// get rounds
$rounds = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties, 'order');

// Return results
RSReturnQueryResults($rounds);
?>