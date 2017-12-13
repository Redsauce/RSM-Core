<?php
//***************************************************
//Description:
//	updates the item properties
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID    = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$line        = $GLOBALS['RS_POST']['line'];
$units       = $GLOBALS['RS_POST']['units'];

// get the stock item type
$stockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stockItem'], $clientID);
// get the stock properties
$stockItemAmountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmount'], $clientID);
$stockItemAmountSoldPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmountSold'], $clientID);

//get the pendingStock item type
$pendingStockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['pendingStock'], $clientID);

// get the pendingStock properties
$pendingStockOperationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockOperationID'], $clientID);
$pendingStockItemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockItemID'], $clientID);
$pendingStockAmountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockAmount'], $clientID);

//begin transaction
$mysqli->begin_transaction();
$res=true;

//get pending stock record
$filterProperties=array();

$returnProperties=array();
$returnProperties[]=array('ID' => $pendingStockItemPropertyID, 'name' => 'itemID');
$returnProperties[]=array('ID' => $pendingStockAmountPropertyID, 'name' => 'amount');

$itemData=getFilteredItemsIDs($pendingStockItemTypeID,$clientID,$filterProperties,$returnProperties,'',false,'',$line);

if(count($itemData)==1){

	$results['itemID'] = $itemData[0]['itemID'];
	$results['oldAmount'] = $itemData[0]['amount'];

	//first recover stock
	$oldAmount=getItemPropertyValue($itemData[0]['itemID'],$stockItemAmountPropertyID,$clientID);

	if($oldAmount>=($units-$itemData[0]['amount'])){
		$result=setPropertyValueByID($stockItemAmountPropertyID, $stockItemTypeID, $itemData[0]['itemID'], $clientID, $oldAmount-($units-$itemData[0]['amount']), '', $RSuserID);

		if($result<0){
			//rollback transaction
			$mysqli->rollback();
			$results['result'] = 'NOK';
			switch($result){
				case -1:
					$results['description'] = 'ERROR UPDATING STOCK AMOUNT';
					break;
				case -2:
					$results['description'] = 'INVALID USER';
					break;
				case -3:
					$results['description'] = 'ERROR UPDATING STOCK AMOUNT';
					break;
			}
			$res=false;
			break;
		}

		if($res){
			$oldAmountSold=getItemPropertyValue($itemData[0]['itemID'],$stockItemAmountSoldPropertyID,$clientID);
			$result=setPropertyValueByID($stockItemAmountSoldPropertyID, $stockItemTypeID, $itemData[0]['itemID'], $clientID, $oldAmountSold+($units-$itemData[0]['amount']), '', $RSuserID);
			if($result<0){
				//rollback transaction
				$mysqli->rollback();
				$results['result'] = 'NOK';
				switch($result){
					case -1:
						$results['description'] = 'ERROR UPDATING STOCK AMOUNT SOLD';
						break;
					case -2:
						$results['description'] = 'INVALID USER';
						break;
					case -3:
						$results['description'] = 'ERROR UPDATING STOCK AMOUNT SOLD';
						break;
				}
				$res=false;
				break;
			}
		}

		//update pending amount of this item for this operation
		$result=setPropertyValueByID($pendingStockAmountPropertyID, $pendingStockItemTypeID, $line, $clientID, $units, '', $RSuserID);

		if($result<0){
			//rollback transaction
			$mysqli->rollback();
			$results['result'] = 'NOK';
			switch($result){
				case -1:
					$results['description'] = 'ERROR UPDATING PENDING STOCK';
					break;
				case -2:
					$results['description'] = 'INVALID USER';
					break;
				case -3:
					$results['description'] = 'ERROR UPDATING PENDING STOCK';
					break;
			}
			$res=false;
			break;
		}
	}else{
		//rollback transaction
		$mysqli->rollback();
		$results['result'] = 'NOK';
		$results['description'] = 'NOT ENOUGH STOCK';
		$res=false;
	}
}else{
	//rollback transaction
	$mysqli->rollback();
	$results['result'] = 'NOK';
	$results['description'] = 'ERROR UPDATING PENDING STOCK';
	$res=false;
}

if($res){
	//commit transaction
	$mysqli->commit();
	$results['result'] = 'OK';
}

// And write XML Response back to the application
RSReturnArrayResults($results);
