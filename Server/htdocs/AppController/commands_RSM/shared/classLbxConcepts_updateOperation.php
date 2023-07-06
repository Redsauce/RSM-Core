<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the item types
$conceptsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);
$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID), 'value' => $operationID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptUnits'], $clientID), 'name' => 'units');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptIVA'], $clientID), 'name' => 'VAT');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptPrice'], $clientID), 'name' => 'price');
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptDeduction'], $clientID), 'name' => 'deduction');

// get operation concepts info
$concepts = IQ_getFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, $returnProperties);

// calculate the operation properties values
$base = 0;
$VAT = 0;
$deduction = 0;

while ($row = $concepts->fetch_assoc()) {
    $base += $row['price'] * $row['units'];
    $VAT += ($row['price'] * $row['units'] * $row['VAT']) / 100;
    $deduction += ($row['price'] * $row['units'] * $row['deduction']) / 100;
}
$total = $base + $VAT - $deduction;



// set the operation properties values
setItemPropertyValue($definitions['operationBase'], $operationsItemTypeID, $operationID, $clientID, round($base, 2), $RSuserID);
setItemPropertyValue($definitions['operationIVA'], $operationsItemTypeID, $operationID, $clientID, round($VAT, 2), $RSuserID);
setItemPropertyValue($definitions['operationDeduction'], $operationsItemTypeID, $operationID, $clientID, round($deduction, 2), $RSuserID);
setItemPropertyValue($definitions['operationTotal'], $operationsItemTypeID, $operationID, $clientID, round($total, 2), $RSuserID);



$results['base'] = round($base, 2);
$results['VAT'] = round($VAT, 2);
$results['deduction'] = round($deduction, 2);
$results['total'] = round($total, 2);

// Return results
RSreturnArrayResults($results);
