<?php
//
// classLbxItemPropertiesFilters_createFilter.php

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$filterName = base64_decode($GLOBALS['RS_POST']['filterName']);
$operatorValue = "AND";

if($clientID!=0&&$clientID!=""){	
	if($itemTypeID!=0&&$itemTypeID!=""){	
		$result = addFilter($clientID,$itemTypeID,$filterName,$operatorValue);
		
		if($result>0){
			$results['result']="OK";
			$results['filterID']=$result;
			
		}else{
			$results['result']="NOK";
			$results['description']="ERROR CREATING FILTER";
		}
	}else{
		$results['result'] = "NOK";
		$results['description'] = "INVALID ITEMTYPE";
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "INVALID CLIENT";
}
			
// And return XML response back to application			
RSReturnArrayResults($results);
?>