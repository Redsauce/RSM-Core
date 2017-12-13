<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID 		= $GLOBALS['RS_POST']['clientID'];
$userID 		= $GLOBALS['RS_POST']['userID'];
$userItemID 	= $GLOBALS['RS_POST']['userItemID'];
$cashItemID 	= $GLOBALS['RS_POST']['cashItemID'];
$remainder 		= $GLOBALS['RS_POST']['remainder'];
$amount 		= $GLOBALS['RS_POST']['amount'];
$sign	 		= $GLOBALS['RS_POST']['sign'];

// get the cash item type
$cashItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['cashRegisters'], $clientID);

// get the cash remainder property
$remainderPropertyID = getClientPropertyID_RelatedWith_byName($definitions['cashRegisterRemainder'], $clientID);

// update the operationID property, assigning the max retrieved +1
setPropertyValueByID($remainderPropertyID, $cashItemTypeID, $cashItemID, $clientID, $remainder, '', $RSuserID);

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
if($sign=="+"){
	$operationType = getValue(getClientListValueID_RelatedWith(getAppListValueID('cashLogOperationTypeIncome'), $clientID), $clientID);
}else{
	$operationType = getValue(getClientListValueID_RelatedWith(getAppListValueID('cashLogOperationTypeOutcome'), $clientID), $clientID);
}

//create log record
$values=array();
$values[]=array('ID' => $cashRegisterPropertyID, 'value' => $cashItemID);
$values[]=array('ID' => $operationPropertyID, 'value' => $operationType);
$values[]=array('ID' => $amountPropertyID, 'value' => $amount);
$values[]=array('ID' => $datePropertyID, 'value' => date('Y-m-d H:i:s'));
$values[]=array('ID' => $userPropertyID, 'value' => $userItemID);

$newCashLogRecordID = createItem($clientID,$values);

$results['result'] = 'OK';
$results['remainder'] = $remainder;



// And write XML response back to the application
RSReturnArrayResults($results);
?>