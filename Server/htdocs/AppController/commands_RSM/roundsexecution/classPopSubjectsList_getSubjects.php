<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$roundID = $GLOBALS['RS_POST']['roundID'];

// get the item type and the properties
$relationItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundSubjectsTestRelations'], $clientID);
$relationRoundPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsRoundID'], $clientID);
$relationSubjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundSubjectsTestRelationsSubjectID'], $clientID);

$subjectItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subject'], $clientID);
$subjectMainPropertyID = getMainPropertyID($subjectItemTypeID, $clientID);
$subjectStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subjectStudyID'], $clientID);

//get Round/Subject Relations
// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $relationSubjectPropertyID, 'name' => 'subjectID');

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $relationRoundPropertyID, 'value' => $roundID);

$subjectIDs = getFilteredItemsIDs($relationItemTypeID, $clientID, $filterProperties, $returnProperties);

$idsList="";
foreach($subjectIDs as $subjectID){
	$idsList.=$subjectID['subjectID'].',';
}

//get Subjects within Relations
$subjects = IQ_getItems($subjectItemTypeID, $clientID, false, trim($idsList, ','));

//return Results
RSReturnQueryResults($subjects);

?>