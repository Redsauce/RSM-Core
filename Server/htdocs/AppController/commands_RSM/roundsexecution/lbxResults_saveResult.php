<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];

// get the "result" item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['result'], $clientID);

// get the properties
$stepUnitAssociatedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultStepUnitAssociatedID'], $clientID);
$stepAssociatedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultStepAssociatedID'], $clientID);
$subjectAssociatedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultSubjectAssociatedID'], $clientID);
$valuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['resultValue'], $clientID);


for ($i = 1; isset($GLOBALS['RS_POST']['result'.$i]); $i++) {
	
	// get result info
	$resultInfo = explode(',', $GLOBALS['RS_POST']['result'.$i]);
	
	$resultID = $resultInfo[0];
	$stepUnitID = $resultInfo[1];
	$stepID = $resultInfo[2];
	$subjectID = $resultInfo[3];
	$value = base64_decode($resultInfo[4]);
	
	
	if ($resultID == 0) {
		// create a new result
		
		$propertiesValues = array(
			array('ID' => $stepUnitAssociatedPropertyID, 'value' => $stepUnitID),
			array('ID' => $stepAssociatedPropertyID, 'value' => $stepID),
			array('ID' => $subjectAssociatedPropertyID, 'value' => $subjectID),
			array('ID' => $valuePropertyID, 'value' => $value)
		);
	
		// create
		$resultID = createItem($clientID, $propertiesValues);
		
	} else {
		// update result
		
		setPropertyValueByID($stepUnitAssociatedPropertyID, $itemTypeID, $resultID, $clientID, $stepUnitID, '', $RSuserID);
		setPropertyValueByID($stepAssociatedPropertyID, $itemTypeID, $resultID, $clientID, $stepID, '', $RSuserID);
		setPropertyValueByID($subjectAssociatedPropertyID, $itemTypeID, $resultID, $clientID, $subjectID, '', $RSuserID);
		
		$propertyType = getPropertyType($valuePropertyID, $clientID);
		if($propertyType=='image'||$propertyType=='file'){
			$values=explode(":",$value);
			setDataPropertyValueByID($valuePropertyID, $itemTypeID, $resultID, $clientID, $values[0], base64_decode($resultInfo[5]), $propertyType, $RSuserID);
		}else{
			setPropertyValueByID($valuePropertyID, $itemTypeID, $resultID, $clientID, $value, $propertyType, $RSuserID);
		}
	}
}



$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
?>