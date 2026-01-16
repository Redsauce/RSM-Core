<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RSMfiltersManagement.php';

// definitions
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$clientID   = $GLOBALS[$cstRS_POST]['clientID'  ];

if ($itemTypeID == '') RSReturnArrayResults(array('result' => 'NOK', 'description' => 'NO ITEM TYPE ID WAS SPECIFIED'));

$returnArray = array();
$results = getFilters($clientID, $itemTypeID);

if ($results) {
    while($result = $results->fetch_assoc())
        $returnArray[] = array('filterID' => $result['filterID'],'filterName' => $result['filterName'],'filterOperator' => $result['filterOperator']);
}

// And return XML response back to application			
RSReturnArrayQueryResults($returnArray);
