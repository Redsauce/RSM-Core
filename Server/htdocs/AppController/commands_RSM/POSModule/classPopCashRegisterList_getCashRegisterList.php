<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];

// get the cashRegisters item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashRegisters'], $clientID);

// get the main property and the account property
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$remainderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterRemainder'], $clientID);
$salesSubAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterSalesSubAccountID'], $clientID);
$cashSubAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterCashSubAccountID'], $clientID);
$lossesSubAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterLossesSubAccountID'], $clientID);

// build the filter properties array
$filterProperties = array();

// build the return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
$returnProperties[] = array('ID' => $remainderPropertyID, 'name' => 'remainderValue');
$returnProperties[] = array('ID' => $salesSubAccountPropertyID, 'name' => 'salesSubAccountID');
$returnProperties[] = array('ID' => $cashSubAccountPropertyID, 'name' => 'cashSubAccountID');
$returnProperties[] = array('ID' => $lossesSubAccountPropertyID, 'name' => 'lossesSubAccountID');

// get the subaccounts
$cashRegisters = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

// Return results
RSReturnQueryResults($cashRegisters);
?>