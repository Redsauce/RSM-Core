<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentStepID = $GLOBALS['RS_POST']['parentStepID'];
$stepUnitMainValue = base64_decode($GLOBALS['RS_POST']['mainValue']);
$stepUnitUnit = base64_decode($GLOBALS['RS_POST']['unit']);
$stepUnitConversionType = base64_decode($GLOBALS['RS_POST']['conversionType']);
$stepUnitIsGlobal = $GLOBALS['RS_POST']['isGlobal'];
$stepUnitStudyID = $GLOBALS['RS_POST']['studyID'];
$stepUnitListType = $GLOBALS['RS_POST']['listType'];

// Get the item types ID
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);
	
//Get the properties IDs
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$studyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsParentStudy'], $clientID);
$parentStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsStepParentID'], $clientID);
$unitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);
$conversionValuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsConversionValue'], $clientID);
$isGlobalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsIsGlobal'], $clientID);
$valuesListPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsValuesList'], $clientID);


//validate required vars
if($clientID!=0&&$clientID!=""){	
	if($stepUnitStudyID!=0&&$stepUnitStudyID!=""){	
		if($parentStepID!=0&&$parentStepID!=""){	
			if($stepUnitMainValue!=""){	
				if($stepUnitIsGlobal!=""){	

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
					
					// create new parameter
					$values = array();
					$values[] = array('ID' => $mainPropertyID, 'value' => $stepUnitMainValue);
					$values[] = array('ID' => $studyPropertyID, 'value' => $stepUnitStudyID);
					$values[] = array('ID' => $parentStepPropertyID, 'value' => $parentStepID);
					$values[] = array('ID' => $unitPropertyID, 'value' => $stepUnitUnit);
					$values[] = array('ID' => $conversionValuePropertyID, 'value' => $stepUnitConversionType);
					$values[] = array('ID' => $isGlobalPropertyID, 'value' => $stepUnitIsGlobal);
					$values[] = array('ID' => $valuesListPropertyID, 'value' => $stepUnitList);
					
					$parameterID = createItem($clientID, $values);
					
					// Build results array
					$results['result'] = 'OK';
					
				}else{
					$results['result'] = "NOK";
					$results['description'] = "EMPTY ISGLOBAL";
				}		
			}else{
				$results['result'] = "NOK";
				$results['description'] = "EMPTY MAIN VALUE";
			}	
		}else{
			$results['result'] = "NOK";
			$results['description'] = "INVALID PARENT STEP ID";
		}		
	}else{
		$results['result'] = "NOK";
		$results['description'] = "INVALID STUDY ID";
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "INVALID CLIENT";
}

// And write XML Response back to the application
RSReturnArrayResults($results);	
?>