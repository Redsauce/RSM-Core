<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

$clientID   = $GLOBALS['RS_POST']['clientID'];
$userID     = $GLOBALS['RS_POST']['userID'];
$MACAddress = base64_decode($GLOBALS['RS_POST']['MACAddress']);

// get the cashRegisters item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashRegisters'], $clientID);

// get the properties
$mainPropertyID                    = getMainPropertyID($itemTypeID, $clientID);
$MACAddressPropertyID              = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterMACAddress'], $clientID);
$remainderPropertyID               = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterRemainder'], $clientID);
$salesSubAccountPropertyID         = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterSalesSubAccountID'], $clientID);
$cashSubAccountPropertyID          = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterCashSubAccountID'], $clientID);
$lossesSubAccountPropertyID        = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterLossesSubAccountID'], $clientID);
$lastClosePropertyID               = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterLastClose'], $clientID);
$cashPaymentMethodPropertyID       = getValue(getClientListValueID_RelatedWith(getAppListValueID('operationPaymentCash'), $clientID), $clientID);
$clientSubAccountAccountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterClientSubAccountAccountID'], $clientID);
$emptyClientSubAccountPropertyID   = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterEmptyClientSubAccountID'], $clientID);
$printerPortPropertyID             = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterPrinterPort'], $clientID);
$printerBaudPropertyID             = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterPrinterBaud'], $clientID);
$printerParityPropertyID           = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterPrinterParity'], $clientID);
$printerBitsPropertyID             = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterPrinterBits'], $clientID);
$printerStopPropertyID             = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterPrinterStop'], $clientID);
$invoicePropertyID                 = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterFinancialInvoiceDocumentID'], $clientID);
$ticketPropertyID                  = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterFinancialTicketDocumentID'], $clientID);

// build the filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => $MACAddressPropertyID, 'value' => $MACAddress, 'mode' => '<-IN');

// build the return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
if($remainderPropertyID!=0) 				$returnProperties[] = array('ID' => $remainderPropertyID, 'name' => 'remainderValue');
if($salesSubAccountPropertyID!=0) 			$returnProperties[] = array('ID' => $salesSubAccountPropertyID, 'name' => 'salesSubAccountID');
if($cashSubAccountPropertyID!=0) 			$returnProperties[] = array('ID' => $cashSubAccountPropertyID, 'name' => 'cashSubAccountID');
if($lossesSubAccountPropertyID!=0) 			$returnProperties[] = array('ID' => $lossesSubAccountPropertyID, 'name' => 'lossesSubAccountID');
if($lastClosePropertyID!=0) 				$returnProperties[] = array('ID' => $lastClosePropertyID, 'name' => 'lastCloseDate');
if($clientSubAccountAccountPropertyID!=0) 	$returnProperties[] = array('ID' => $clientSubAccountAccountPropertyID, 'name' => 'clientSubAccountAccountID');
if($emptyClientSubAccountPropertyID!=0) 	$returnProperties[] = array('ID' => $emptyClientSubAccountPropertyID, 'name' => 'emptyClientID');
if($printerPortPropertyID!=0) 				$returnProperties[] = array('ID' => $printerPortPropertyID, 'name' => 'printerPort');
if($printerBaudPropertyID!=0) 				$returnProperties[] = array('ID' => $printerBaudPropertyID, 'name' => 'printerBaud');
if($printerParityPropertyID!=0) 			$returnProperties[] = array('ID' => $printerParityPropertyID, 'name' => 'printerParity');
if($printerBitsPropertyID!=0) 				$returnProperties[] = array('ID' => $printerBitsPropertyID, 'name' => 'printerBits');
if($printerStopPropertyID!=0) 				$returnProperties[] = array('ID' => $printerStopPropertyID, 'name' => 'printerStop');
if($invoicePropertyID!=0) 					$returnProperties[] = array('ID' => $invoicePropertyID, 'name' => 'invoiceDocument');
if($ticketPropertyID!=0) 					$returnProperties[] = array('ID' => $ticketPropertyID, 'name' => 'ticketDocument');

// get the subaccounts
$cashRegisters = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

$results=array();
while($row=$cashRegisters->fetch_assoc()){
	$results[]=$row;
}

//get globals
$theQuery = 'SELECT RS_NAME,RS_VALUE FROM rs_globals WHERE RS_CLIENT_ID = '.$clientID.' AND (RS_NAME LIKE "pos.%" OR RS_NAME LIKE "rsm.%")';

$GlobalsResult = RSQuery($theQuery);

if(count($results)>0){
	
	$results[0]["cashPaymentMethod"]=$cashPaymentMethodPropertyID;
	
	while($row=$GlobalsResult->fetch_assoc()){
		$results[0][$row["RS_NAME"]]=$row["RS_VALUE"];	
	}
}else{
	$results[0]=array("result"=>"NOK","description"=>"CASH REGISTER NOT FOUND");
}

// Return results
RSReturnArrayQueryResults($results);
?>