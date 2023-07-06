<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Retrieve POST variables
isset($GLOBALS['RS_POST']['clientID'   ]) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['filterID'   ]) ? $filterID = $GLOBALS['RS_POST']['filterID'] : dieWithError(400);

if ($filterID == "") {
    $filterID = "0";
}

$filterClauses = getFilterClauses($clientID, $filterID);

$results['conditionPropertyIDs'] = "";
$results['operators'           ] = "";
$results['conditionValues'     ] = "";

foreach ($filterClauses as $filterClause) {
    $results['conditionPropertyIDs'] .=               $filterClause['conditionPropertyID'].";";
    $results['operators'           ] .=               $filterClause['conditionOperator'  ].";";
    $results['conditionValues'     ] .= base64_encode($filterClause['conditionValue'     ]).";";
}

$results['conditionPropertyIDs'] = rtrim($results['conditionPropertyIDs'], ";");
$results['operators'           ] = rtrim($results['operators'           ], ";");
$results['conditionValues'     ] = rtrim($results['conditionValues'     ], ";");
$results['filterProperties'    ] = "";

$filterProperties = getFilterProperties($clientID, $filterID);

foreach ($filterProperties as $filterProperty) {
    $results['filterProperties'] .= $filterProperty['conditionPropertyID'].";";
}

$results['filterProperties'] = rtrim($results['filterProperties'], ";");

// Build query to get filter operator
$theQuery = 'SELECT `RS_OPERATOR` FROM `rs_item_type_filters` WHERE `RS_CLIENT_ID`='.$clientID.' AND `RS_FILTER_ID`='.$filterID.'';
    
// Execute the query
$result = RSquery($theQuery);
    
if ($result && $result->num_rows == 1) {
    $res = $result->fetch_assoc();
    $results['filterType'] = $res["RS_OPERATOR"];
} else {
    $results['filterType'] = "";
}

// And return XML response back to application
RSReturnArrayResults($results);
