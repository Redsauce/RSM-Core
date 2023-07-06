<?php
//
// classLbxItemPropertiesFilters_updateFilter.php

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$filterID = (($GLOBALS['RS_POST']['filterID']=="")?("0"):($GLOBALS['RS_POST']['filterID']));
$filterName = base64_decode($GLOBALS['RS_POST']['filterName']);

if ($clientID!=0&&$clientID!="") {
    if ($filterID!=0&&$filterID!="") {
        $result = updateFilterName($clientID, $filterID, $filterName);
        
        if ($result==1) {
            $results['result']="OK";
        } else {
            $results['result']="NOK";
            $results['description']="ERROR UPDATING FILTER";
        }
    } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID FILTER";
    }
} else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID CLIENT";
}

// And return XML response back to application
RSReturnArrayResults($results);
