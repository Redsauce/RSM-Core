<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID 			= $GLOBALS['RS_POST']['clientID'];
$userID 			= $GLOBALS['RS_POST']['userID'];
$salesSubAccountID 	= $GLOBALS['RS_POST']['subAccountID'];
$operationID 		= $GLOBALS['RS_POST']['operationID'];
$paymentParts 		= explode(";",$GLOBALS['RS_POST']['paymentParts']);
$clientSubAccountID = $GLOBALS['RS_POST']['clientSubAccountID'];
$cashItemID	        = $GLOBALS['RS_POST']['cashItemID'];

//insert close operation
// get the subAccount item type
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
// get the operations item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get the operations properties
$subAccountPropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'	], $clientID);
$operationIDPropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'	], $clientID);
$invoiceDatePropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'	], $clientID);
$payMentMethodPropertyID	= getClientPropertyID_RelatedWith_byName($definitions['operationPayMethod'		], $clientID);
$basePropertyID 			= getClientPropertyID_RelatedWith_byName($definitions['operationBase'			], $clientID);
$IVAPropertyID 				= getClientPropertyID_RelatedWith_byName($definitions['operationIVA'			], $clientID);
$deductionPropertyID 		= getClientPropertyID_RelatedWith_byName($definitions['operationDeduction'		], $clientID);
$totalPropertyID			= getClientPropertyID_RelatedWith_byName($definitions['operationTotal'			], $clientID);
$payDatePropertyID			= getClientPropertyID_RelatedWith_byName($definitions['operationPayDate'		], $clientID);
$cashIDPropertyID			= getClientPropertyID_RelatedWith_byName($definitions['operationCashID'			], $clientID);

//get cash payment method
$cashPaymentMethod = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationPaymentCash'), $clientID), $clientID);

// calculate the next ID available for the operation (the max ID for the current year and current account)
//get account
$accountID = getItemPropertyValue($clientSubAccountID, getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID);

$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

$subAccounts = array();
if ($subAccountsQueryResults) {
    while ($row = $subAccountsQueryResults->fetch_assoc())
        $subAccounts[] = $row['ID'];
}

$filterProperties = array();
$filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')-1).'-12-31', 'mode' => 'AFTER');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')+1).'-01-01', 'mode' => 'BEFORE');

$returnProperties[] = array('ID' => $operationIDPropertyID, 'name' => 'operationID');

$currentYearOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

$maxID = 0;
if ($currentYearOperations) {
    while ($row = $currentYearOperations->fetch_assoc())
        if ($row['operationID'] > $maxID)
            $maxID = $row['operationID']; // update maxID
}

// update the operationID property, assigning the max retrieved +1
setPropertyValueByID($operationIDPropertyID, $itemTypeID, $operationID, $clientID, $maxID+1, '', $RSuserID);
$clientOperationID=$maxID+1;

// set operation subAccount(client) property to the client passed
setPropertyValueByID($subAccountPropertyID, $itemTypeID, $operationID, $clientID, $clientSubAccountID, '', $RSuserID);

// set operation invoiceDate property to the current date
setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $operationID, $clientID, date('Y-m-d'), '', $RSuserID);

// set operation cashID property to the current cash
setPropertyValueByID($cashIDPropertyID, $itemTypeID, $operationID, $clientID, $cashItemID, '', $RSuserID);

// set operation payment method passed
$paymentString="";
for($i=1;$i<count($paymentParts);$i+=2){
	$paymentString.=base64_decode($paymentParts[$i])."; ";
}
$paymentString=trim($paymentString,"; ");

setPropertyValueByID($payMentMethodPropertyID, $itemTypeID, $operationID, $clientID, $paymentString, '', $RSuserID);

if($paymentString==$cashPaymentMethod){	
	// set operation payDate property to the current date
	setPropertyValueByID($payDatePropertyID, $itemTypeID, $operationID, $clientID, date('Y-m-d'), '', $RSuserID);
}

//add sale operations
// calculate the next ID available for the operation (the max ID for the current year and current account)
//get account
$accountID = getItemPropertyValue($salesSubAccountID, getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID);

$subAccountsQueryResults = IQ_getFilteredItemsIDs($subAccountsItemTypeID, $clientID, $filterProperties, array());

$subAccounts = array();

if ($subAccountsQueryResults) {
    while ($row = $subAccountsQueryResults->fetch_assoc())
        $subAccounts[] = $row['ID'];
}

$filterProperties = array();
$filterProperties[] = array('ID' => $subAccountPropertyID, 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')-1).'-12-31', 'mode' => 'AFTER');
$filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y')+1).'-01-01', 'mode' => 'BEFORE');

$returnProperties[] = array('ID' => $operationIDPropertyID, 'name' => 'operationID');

$currentYearOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

$maxID = 0;

if ($currentYearOperations) {
    while ($row = $currentYearOperations->fetch_assoc())
        if ($row['operationID'] > $maxID) 
            $maxID = $row['operationID']; // update maxID
}

for($i=0;$i<count($paymentParts);$i+=2){
	// create a new operation
	$maxID++;
	$values = array();
	$values[]=array('ID' => $subAccountPropertyID, 'value' => $salesSubAccountID);
	$values[]=array('ID' => $operationIDPropertyID, 'value' => $maxID);
	$values[]=array('ID' => $invoiceDatePropertyID, 'value' => date('Y-m-d'));
	$values[]=array('ID' => $payMentMethodPropertyID, 'value' => base64_decode($paymentParts[$i+1]));
	//$values[]=array('ID' => $basePropertyID, 'value' => getPropertyValue($definitions['operationBase'], $itemTypeID, $operationID, $clientID));
	//$values[]=array('ID' => $IVAPropertyID, 'value' => getPropertyValue($definitions['operationIVA'], $itemTypeID, $operationID, $clientID));
	//$values[]=array('ID' => $deductionPropertyID, 'value' => getPropertyValue($definitions['operationDeduction'], $itemTypeID, $operationID, $clientID));
	$values[]=array('ID' => $totalPropertyID, 'value' => $paymentParts[$i]);
	$values[]=array('ID' => $cashIDPropertyID, 'value' => $cashItemID);
	
	if(base64_decode($paymentParts[$i+1])==$cashPaymentMethod)
		$values[]=array('ID' => $payDatePropertyID, 'value' => date('Y-m-d')); // include payDate property

	$operationID = createItem($clientID, $values);
}

$results['result'] = 'OK';
$results['ID'] = $operationID;
$results['clientOperationID'] = $clientOperationID;

// And write XML response back to the application
RSReturnArrayResults($results);
?>