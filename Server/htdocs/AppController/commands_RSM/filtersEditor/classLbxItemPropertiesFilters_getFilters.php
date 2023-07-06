<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RSMfiltersManagement.php';

// definitions
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$clientID   = $GLOBALS['RS_POST']['clientID'];

if ($itemTypeID == '') {
    RSreturnArrayResults(array('result' => 'NOK', 'description' => 'NO ITEM TYPE ID WAS SPECIFIED'));
}

$returnArray = array();
$results = getFilters($clientID, $itemTypeID);

if ($results) {
    while ($result = $results->fetch_assoc()) {
        $returnArray[] = array('filterID' => $result['filterID'], 'filterName' => $result['filterName'], 'filterOperator' => $result['filterOperator']);
    }
}

// And return XML response back to application
RSreturnArrayQueryResults($returnArray);
