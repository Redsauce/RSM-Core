<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$stepUnitID   = $GLOBALS['RS_POST']['stepUnitID'];
$stepUnitMainValue = base64_decode($GLOBALS['RS_POST']['mainValue']);
$stepUnitUnit = base64_decode($GLOBALS['RS_POST']['unit']);
$stepUnitConversionValue = base64_decode($GLOBALS['RS_POST']['conversionValue']);
$stepUnitIsGlobal = $GLOBALS['RS_POST']['isGlobal'];
$stepUnitListType = $GLOBALS['RS_POST']['listType'];

$stepUnitList = '';

if (($stepUnitListType == '0' || $stepUnitListType == '1') && (isset($GLOBALS['RS_POST']['value1']))) {
	$stepUnitValuesList = array();
	
	for ($i = 1; isset($GLOBALS['RS_POST']['value'.$i]); $i++) {
		$tmpValue = base64_decode($GLOBALS['RS_POST']['value'.$i]);
		
		if (strpos($tmpValue, ',') === false) {
			$stepUnitValuesList[] = $tmpValue;
		} else {
			// The values must not contain the "," character: return NOK
			$results['result'] = 'NOK';
			
			RSReturnArrayResults($results);
			exit;
		}
	}
	
	// build the list
	$stepUnitList = $stepUnitListType.','.implode(',', $stepUnitValuesList);
}


// update values
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

$stepUnitMainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$stepUnitUnitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);
$stepUnitConversionValuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsConversionValue'], $clientID);
$stepIsGlobalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsIsGlobal'], $clientID);
$valuesListPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsValuesList'], $clientID);

setPropertyValueByID($stepUnitMainPropertyID ,$itemTypeID, $stepUnitID, $clientID, $stepUnitMainValue, '', $RSuserID);
setPropertyValueByID($stepUnitUnitPropertyID ,$itemTypeID, $stepUnitID, $clientID, $stepUnitUnit, '', $RSuserID);
setPropertyValueByID($stepUnitConversionValuePropertyID ,$itemTypeID, $stepUnitID, $clientID, $stepUnitConversionValue, '', $RSuserID);
setPropertyValueByID($stepIsGlobalPropertyID ,$itemTypeID, $stepUnitID, $clientID, $stepUnitIsGlobal, '', $RSuserID);
setPropertyValueByID($valuesListPropertyID ,$itemTypeID, $stepUnitID, $clientID, $stepUnitList, '', $RSuserID);


$results['result'] = 'OK';

RSReturnArrayResults($results);
?>