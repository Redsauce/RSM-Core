<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$roundName = base64_decode($GLOBALS['RS_POST']['roundName']);
$studyID =  $GLOBALS['RS_POST']['studyID'];


// get the item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['roundsplanning'], $clientID);
$orderPropertyID =getClientPropertyID_RelatedWith_byName($definitions['roundsplanningOrder'], $clientID);
$associatedStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedStudyID'], $clientID);

//Next, get the max order for the rounds
$returnProperties = array();
$returnProperties[] = array('ID' => $orderPropertyID, 'name' => 'order');

//build the filter
$filters = array();

$roundsorders = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

//Next search the maximun order
$maxOrder = -1;
for ($i=0;$i<count($roundsorders); $i++){
	if ($roundsorders[$i]['order']>$maxOrder){
		$maxOrder = 	$roundsorders[$i]['order']	;
	}
}

//increments the maximum order
$maxOrder ++;
	
	
// create concept item
$values = array();
$values[]=array('ID' => getClientPropertyID_RelatedWith_byName($definitions['roundsplanningName'], $clientID), 'value' => $roundName);
$values[]=array('ID' => getClientPropertyID_RelatedWith_byName($definitions['roundsplanningOrder'], $clientID), 'value' => $maxOrder);
$values[]=array('ID' => getClientPropertyID_RelatedWith_byName($definitions['roundsplanningAssociatedStudyID'], $clientID), 'value' => $studyID);


$roundID = createItem($clientID, $values);

$results['ID'] = $roundID;
$results['name'] = $roundName;
$results['order'] = $maxOrder;
$results['studyID']=$studyID;

RSReturnArrayResults($results);
?>