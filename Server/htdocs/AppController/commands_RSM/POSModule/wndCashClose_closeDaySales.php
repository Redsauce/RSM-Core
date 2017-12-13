<?php
//***************************************************
//Description:
//	Create a relationship between two operations
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID 			= $GLOBALS['RS_POST']['clientID'];
$userID 			= $GLOBALS['RS_POST']['userID'];
$userItemID 		= $GLOBALS['RS_POST']['userItemID'];
$cashRegisterItemID	= $GLOBALS['RS_POST']['cashRegisterItemID'];
$lastClose 			= $GLOBALS['RS_POST']['lastClose'];
$salesSubAccountID 	= $GLOBALS['RS_POST']['salesSubAccountID'];
$cashSubAccountID 	= $GLOBALS['RS_POST']['cashSubAccountID'];
$matchTotal 		= $GLOBALS['RS_POST']['matchTotal'];
$operationIDs 		= $GLOBALS['RS_POST']['operationIDs'];
$operationDate 		= $GLOBALS['RS_POST']['operationDate'];
$remainder 			= $GLOBALS['RS_POST']['remainder'];

//$operation_1 	= $GLOBALS['RS_POST']['operation_1'	];  // a bank statement
//$operation_2 	= $GLOBALS['RS_POST']['operation_2'	];  // an invoice
//$setPayDate 	= $GLOBALS['RS_POST']['setPayDate'	];

//insert close operation
// get the subAccount item type
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get the operations properties
$subAccountPropertyID 	= getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'	], $clientID);
$operationIDPropertyID 	= getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'	], $clientID);
$invoiceDatePropertyID 	= getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'	], $clientID);
$basePropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationBase'			], $clientID);
$ivaPropertyID 			= getClientPropertyID_RelatedWith_byName($definitions['operationIVA'			], $clientID);
$deductionPropertyID 	= getClientPropertyID_RelatedWith_byName($definitions['operationDeduction'		], $clientID);
$totalPropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationTotal'			], $clientID);


// calculate the next ID available for the operations to import (the max ID for the current year and current account)
//get account
$accountID = getItemPropertyValue($cashSubAccountID, getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID);

$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

$subAccounts = array();
while ($row = $subAccountsQueryResults->fetch_assoc()) {
	$subAccounts[] = $row['ID'];
}

$filterProperties = array();
$filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')-1).'-12-31', 'mode' => 'AFTER');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')+1).'-01-01', 'mode' => 'BEFORE');

$returnProperties[] = array('ID' => $operationIDPropertyID, 'name' => 'operationID');

$currentYearOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

$maxID = 0;
while ($row = $currentYearOperations->fetch_assoc()) {
	if ($row['operationID'] > $maxID) {
		// update maxID
		$maxID = $row['operationID'];
	}
}

$maxID++;

// create new item
$values = array();
$values[]=array('ID' => $subAccountPropertyID, 'value' => $GLOBALS['RS_POST']['cashSubAccountID']);
$values[]=array('ID' => $operationIDPropertyID, 'value' => $maxID);
$values[]=array('ID' => $invoiceDatePropertyID, 'value' => $operationDate);
$values[]=array('ID' => $ivaPropertyID, 'value' => '0');
$values[]=array('ID' => $deductionPropertyID, 'value' => '0');

if($matchTotal=="True"){
	$values[]=array('ID' => $basePropertyID, 'value' => round($GLOBALS['RS_POST']['cashRegisterAmount']-$remainder,2));
	$values[]=array('ID' => $totalPropertyID, 'value' => round($GLOBALS['RS_POST']['cashRegisterAmount']-$remainder,2));
}else{
	$values[]=array('ID' => $basePropertyID, 'value' => round($GLOBALS['RS_POST']['daySalesTotal']-$remainder,2));
	$values[]=array('ID' => $totalPropertyID, 'value' => round($GLOBALS['RS_POST']['daySalesTotal']-$remainder,2));
}

$newCashItemID = createItem($clientID,$values);

//add log record
// get the cashLog item type
$cashLogItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashLog'], $clientID);

// get the cashLog properties
$cashRegisterPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashLogCashRegisterID'], $clientID);
$operationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashLogOperation'], $clientID);
$amountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashLogAmount'], $clientID);
$datePropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashLogDate'], $clientID);
$userPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashLogUserID'], $clientID);

//get proper operation list value
$operationType = getValue(getClientListValueID_RelatedWith(getAppListValueID('cashLogOperationTypeClose'), $clientID), $clientID);

if($matchTotal=="True"){
	$amount = round($GLOBALS['RS_POST']['cashRegisterAmount']-$remainder,2);
}else{
	$amount = round($GLOBALS['RS_POST']['daySalesTotal']-$remainder,2);
}

//create log record
$values[]=array('ID' => $cashRegisterPropertyID, 'value' => $cashRegisterItemID);
$values[]=array('ID' => $operationPropertyID, 'value' => $operationType);
$values[]=array('ID' => $amountPropertyID, 'value' => $amount);
$values[]=array('ID' => $datePropertyID, 'value' => date('Y-m-d H:i:s'));
$values[]=array('ID' => $userPropertyID, 'value' => $userItemID);

$newCashLogRecordID = createItem($clientID,$values);



if($matchTotal=="True"){
	// create negative cash
	$maxID++;
	$values = array();
	$values[]=array('ID' => $subAccountPropertyID, 'value' => $GLOBALS['RS_POST']['cashSubAccountID']);
	$values[]=array('ID' => $operationIDPropertyID, 'value' => $maxID);
	$values[]=array('ID' => $invoiceDatePropertyID, 'value' => $operationDate);
	$values[]=array('ID' => $basePropertyID, 'value' => round(-$GLOBALS['RS_POST']['cashRegisterDifference'],2));
	$values[]=array('ID' => $totalPropertyID, 'value' => round(-$GLOBALS['RS_POST']['cashRegisterDifference'],2));
	$values[]=array('ID' => $ivaPropertyID, 'value' => '0');
	$values[]=array('ID' => $deductionPropertyID, 'value' => '0');

	$newCashLossesItemID = createItem($clientID,$values);

	// create losses item
	// calculate the next ID available for the operations to import (the max ID for the current year and current account)
	//get account
	$accountID = getItemPropertyValue($GLOBALS['RS_POST']['lossesSubAccountID'], getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), $clientID);

	// build filter properties array
	$filterProperties = array();
	$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID);

	$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

	$subAccounts = array();
	while ($row = $subAccountsQueryResults->fetch_assoc()) {
		$subAccounts[] = $row['ID'];
	}

	$filterProperties = array();
	$filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
	$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')-1).'-12-31', 'mode' => 'AFTER');
	$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')+1).'-01-01', 'mode' => 'BEFORE');

	$returnProperties[] = array('ID' => $operationIDPropertyID, 'name' => 'operationID');

	$currentYearOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

	$maxID = 0;
	while ($row = $currentYearOperations->fetch_assoc()) {
		if ($row['operationID'] > $maxID) $maxID = $row['operationID']; // update maxID
	}

	$maxID++;

	$values = array();
	$values[]=array('ID' => $subAccountPropertyID, 'value' => $GLOBALS['RS_POST']['lossesSubAccountID']);
	$values[]=array('ID' => $operationIDPropertyID, 'value' => $maxID);
	$values[]=array('ID' => $invoiceDatePropertyID, 'value' => $operationDate);
	$values[]=array('ID' => $totalPropertyID, 'value' => round(-$GLOBALS['RS_POST']['cashRegisterDifference'],2));
	$values[]=array('ID' => $basePropertyID, 'value' => round(-$GLOBALS['RS_POST']['cashRegisterDifference'],2));
	$values[]=array('ID' => $ivaPropertyID, 'value' => '0');
	$values[]=array('ID' => $deductionPropertyID, 'value' => '0');

	$newLossesItemID = createItem($clientID,$values);

	//add log record
	//get proper operation list value
	$operationType = getValue(getClientListValueID_RelatedWith(getAppListValueID('cashLogOperationTypeMatch'), $clientID), $clientID);

	//create log record
	$values=array();
	$values[]=array('ID' => $cashRegisterPropertyID, 'value' => $cashRegisterItemID);
	$values[]=array('ID' => $operationPropertyID, 'value' => $operationType);
	$values[]=array('ID' => $amountPropertyID, 'value' => round(-$GLOBALS['RS_POST']['cashRegisterDifference'],2));
	$values[]=array('ID' => $datePropertyID, 'value' => date('Y-m-d H:i:s'));
	$values[]=array('ID' => $userPropertyID, 'value' => $userItemID);

	$newCashLogRecordID = createItem($clientID,$values);
}

//update remainder
$cashRegisterItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashRegisters'], $clientID);

// set the operation properties values
if($matchTotal=="True"){
	setItemPropertyValue($definitions['cashRegisterRemainder'], $cashRegisterItemTypeID, $cashRegisterItemID, $clientID, $GLOBALS['RS_POST']['cashRegisterAmount'], $RSuserID);
	$results['remainderValue']=$GLOBALS['RS_POST']['cashRegisterAmount'];
}else{
	setItemPropertyValue($definitions['cashRegisterRemainder'], $cashRegisterItemTypeID, $cashRegisterItemID, $clientID, $GLOBALS['RS_POST']['daySalesTotal'], $RSuserID);
	$results['remainderValue']=$GLOBALS['RS_POST']['daySalesTotal'];
}

//relate operations
// get operations item type
$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get some properties we will need
$relOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);
$totalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationTotal'], $clientID);
$totalPropertyType = getPropertyType($totalPropertyID, $clientID);

$operationsList=split(",",$operationIDs);

for($i=0;$i<count($operationsList);$i++){
	// add operations into the properly lists
	addIdentifier($newCashItemID, $operationsItemTypeID, $operationsList[$i], $relOperationsPropertyID, $clientID, $RSuserID);
	addIdentifier($operationsList[$i], $operationsItemTypeID, $newCashItemID, $relOperationsPropertyID, $clientID, $RSuserID);
}

if($matchTotal=="True"){
	addIdentifier($newCashLossesItemID, $operationsItemTypeID, $newLossesItemID, $relOperationsPropertyID, $clientID, $RSuserID);
	addIdentifier($newLossesItemID, $operationsItemTypeID, $newCashLossesItemID, $relOperationsPropertyID, $clientID, $RSuserID);
}

//update lastClose
setItemPropertyValue($definitions['cashRegisterLastClose'], $cashRegisterItemTypeID, $cashRegisterItemID, $clientID, $operationDate, $RSuserID);
$results['lastCloseDate']=$operationDate;

// And write XML response back to the application
RSReturnArrayResults($results);
?>
