<?php
//***************************************************
//Description:
//	updates the item properties
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$item = $GLOBALS['RS_POST']['item'];

// get the stock item type
$stockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stockItem'], $clientID);
// get the stock properties
$stockItemAmountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmount'], $clientID);
$stockItemAmountSoldPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmountSold'], $clientID);
$stockItemNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemName'], $clientID);
$stockItemIdentifierPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemIdentifier'], $clientID);
$stockItemSalePricePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemSalePrice'], $clientID);

//get the pendingStock item type
$pendingStockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['pendingStock'], $clientID);
// get the pendingStock properties
$pendingStockOperationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockOperationID'], $clientID);
$pendingStockItemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockItemID'], $clientID);
$pendingStockAmountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockAmount'], $clientID);

//begin transaction
$mysqli->begin_transaction();

$res=true;
$itemsRestored=array();

$filterProperties=array();
$filterProperties[]=array('ID' => $pendingStockOperationPropertyID, 'value' => $operationID);

$returnProperties=array();
$returnProperties[]=array('ID' => $pendingStockItemPropertyID, 'name' => 'itemID');
$returnProperties[]=array('ID' => $pendingStockAmountPropertyID, 'name' => 'amount');

if($item!=""){
	$itemsData=getFilteredItemsIDs($pendingStockItemTypeID,$clientID,$filterProperties,$returnProperties,'',false,'',$item);
}else{
	$itemsData=getFilteredItemsIDs($pendingStockItemTypeID,$clientID,$filterProperties,$returnProperties);
}

//check product exists
if(count($itemsData)>0){
	foreach($itemsData as $itemData){

		//first recover stock and update amount
		$oldAmount=getItemPropertyValue($itemData['itemID'],$stockItemAmountPropertyID,$clientID);
		
		if($itemData['amount']>0||$oldAmount>=-$itemData['amount']){
			
			$result=setPropertyValueByID($stockItemAmountPropertyID, $stockItemTypeID, $itemData['itemID'], $clientID, $oldAmount+$itemData['amount'], '', $RSuserID);
			
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
				$oldAmountSold=getItemPropertyValue($itemData['itemID'],$stockItemAmountSoldPropertyID,$clientID);
				$result=setPropertyValueByID($stockItemAmountSoldPropertyID, $stockItemTypeID, $itemData['itemID'], $clientID, $oldAmountSold-$itemData['amount'], '', $RSuserID);
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
			
			//remove pendingStock
			deleteItem($pendingStockItemTypeID, $itemData['ID'], $clientID);
			
		}else{
			$results['result'] = "NOK";
			$results['description'] = "NOT ENOUGH STOCK";
		}
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "PRODUCT NOT FOUND";
}


if($res){
	//commit transaction
	$mysqli->commit();
	$results['result'] = 'OK';
	foreach($itemsData as $itemData){
		$results['items'] .= $itemData['itemID'].';';
	}
	$results['items']=rtrim($results['items'],";");
}else{
	// build filter
	$filterProperties = array();
	
	$returnProperties = array();
	$returnProperties[] = array('ID' => $stockItemNamePropertyID, 'name' => 'name');
	$returnProperties[] = array('ID' => $stockItemIdentifierPropertyID, 'name' => 'identifier');
	$returnProperties[] = array('ID' => $stockItemSalePricePropertyID, 'name' => 'price');
	
	foreach($itemsData as $itemData){
		
		// get item and properties
		$productResult = getFilteredItemsIDs($stockItemTypeID,$clientID,$filterProperties,$returnProperties,'',false,'',$itemData['itemID']);
		
		$results['items'] .= base64_encode($itemData['ID']).',';
		$results['items'] .= base64_encode($itemData['itemID']).',';
		$results['items'] .= base64_encode($productResult[0]['identifier']).',';
		$results['items'] .= base64_encode($productResult[0]['name']).',';
		$results['items'] .= base64_encode($itemData['amount']).',';
		$results['items'] .= base64_encode($productResult[0]['price']).';';
	}
	$results['items']=rtrim($results['items'],";");
}


// And write XML Response back to the application
RSReturnArrayResults($results);
?>