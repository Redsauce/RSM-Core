<?php
//
// classLbxItemPropertiesFilters_deleteFilter.php

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$filterID = (($GLOBALS['RS_POST']['filterID']=="")?("0"):($GLOBALS['RS_POST']['filterID']));

if ($clientID!=0&&$clientID!="") {
    if ($filterID!=0&&$filterID!="") {
        $result = deleteFilter($clientID, $filterID);

        if ($result==1) {
            $results['result']="OK";

        } else {
            $results['result']="NOK";
            $results['description']="ERROR DELETING FILTER";
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
