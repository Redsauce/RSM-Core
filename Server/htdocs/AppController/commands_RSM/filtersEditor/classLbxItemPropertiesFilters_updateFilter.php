<?php
//
// classLbxItemPropertiesFilters_updateFilter.php

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// definitions
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$filterID = (($GLOBALS[$cstRS_POST]['filterID']=="")?("0"):($GLOBALS[$cstRS_POST]['filterID']));
$filterName = base64_decode($GLOBALS[$cstRS_POST]['filterName']);

if($clientID!=0&&$clientID!=""){	
	if($filterID!=0&&$filterID!=""){	
		$result = updateFilterName($clientID,$filterID,$filterName);
		
		if($result==1){
			$results['result']="OK";
		}else{
			$results['result']="NOK";
			$results['description']="ERROR UPDATING FILTER";
		}
	}else{
		$results['result'] = "NOK";
		$results['description'] = "INVALID FILTER";
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "INVALID CLIENT";
}
			
// And return XML response back to application			
RSReturnArrayResults($results);
