<?php
//***************************************************
//Description:
//	updates the item properties
//  the force parameter ignores stocks of 0 or less
//***************************************************


// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

$clientID    = $GLOBALS['RS_POST']['clientID'   ];
$operationID = $GLOBALS['RS_POST']['operationID'];
$itemID      = $GLOBALS['RS_POST']['itemID'     ];
$units       = $GLOBALS['RS_POST']['units'      ];
$force       = $GLOBALS['RS_POST']['force'      ];

// get the stock item type
$stockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stockItem'], $clientID);

// get the stock properties
$stockItemNamePropertyID       = getClientPropertyID_RelatedWith_byName($definitions['stockItemName'      ], $clientID);
$stockItemIdentifierPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemIdentifier'], $clientID);
$stockItemSalePricePropertyID  = getClientPropertyID_RelatedWith_byName($definitions['stockItemSalePrice' ], $clientID);
$stockItemAmountPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmount'    ], $clientID);
$stockItemAmountSoldPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stockItemAmountSold'], $clientID);

// get the pendingStock item type
$pendingStockItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['pendingStock'], $clientID);

// get the pendingStock properties
$pendingStockOperationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['pendingStockOperationID'], $clientID);
$pendingStockItemPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['pendingStockItemID'     ], $clientID);
$pendingStockAmountPropertyID    = getClientPropertyID_RelatedWith_byName($definitions['pendingStockAmount'     ], $clientID);


// build filter
$filterProperties   = array();
$returnProperties   = array();

$returnProperties[] = array('ID' => $stockItemNamePropertyID,       'name' => 'name'      );
$returnProperties[] = array('ID' => $stockItemIdentifierPropertyID, 'name' => 'identifier');
$returnProperties[] = array('ID' => $stockItemAmountPropertyID,     'name' => 'amount'    );
$returnProperties[] = array('ID' => $stockItemAmountSoldPropertyID, 'name' => 'amountSold');
$returnProperties[] = array('ID' => $stockItemSalePricePropertyID,  'name' => 'price'     );

// get item and properties
$productResult = getFilteredItemsIDs($stockItemTypeID,$clientID,$filterProperties,$returnProperties,'',false,'',$itemID);

if(count($productResult) == 1){
	if(($productResult[0]['amount'] >= $units) || ($force == '1')){
		
		//begin transaction
		$mysqli->begin_transaction();
		$res = true;
	
		$result = setPropertyValueByID($stockItemAmountPropertyID, $stockItemTypeID, $itemID, $clientID, $productResult[0]['amount']-$units, '', $RSuserID);
		
		if($result < 0){
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
		}
		
		if($res){
			$result=setPropertyValueByID($stockItemAmountSoldPropertyID, $stockItemTypeID, $itemID, $clientID, $productResult[0]['amountSold'] + $units, '', $RSuserID);
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
			}
		}
		
		if($res){
			//create pendingStock record
			$values   = array();
			$values[] = array('ID' => $pendingStockOperationPropertyID  , 'value' => $operationID );
			$values[] = array('ID' => $pendingStockItemPropertyID       , 'value' => $itemID      );
			$values[] = array('ID' => $pendingStockAmountPropertyID     , 'value' => $units       );
			$newID    = createItem   ($clientID, $values);
		}
		
		if($res){
			//commit transaction
			$mysqli->commit();
			$results['result'       ] = 'OK';
			$results['rowIdentifier'] = $newID;
			$results['name'         ] = $productResult[0]['name'];
			$results['identifier'   ] = $productResult[0]['identifier'];
			$results['price'        ] = $productResult[0]['price'];
		}
	}else{
		$results['result'     ] = "NOK";
		$results['description'] = "NOT ENOUGH STOCK";
		$results['name'       ] = $productResult[0]['name'];
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "PRODUCT NOT FOUND";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>