<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$IDs = explode(";",$GLOBALS['RS_POST']['IDs']);
$parentID = $GLOBALS['RS_POST']['parentID'];

if($clientID!=0&&$clientID!=""){
	if($parentID!=""){

		//begin transaction
		$mysqli->begin_transaction();

		foreach($IDs as $ID){
			$item=explode(",",$ID);
			$itemID=$item[0];
			$itemTypeID=$item[1];
			$parentPropertyID=$item[2];

			if($itemTypeID!=0&&$itemTypeID!=""){
				if ($itemID!=0&&$itemID!=""){
					if($parentPropertyID!=""){
						if(isPropertyVisible($RSuserID,$parentPropertyID,$clientID)){
							if($parentID!=0){
								//check parent exists
								$parentItemTypeID=getClientPropertyReferredItemType($parentPropertyID, $clientID);
								if($parentItemTypeID!=0){
									if(count(getItems($parentItemTypeID,$clientID,true,$parentID))==0){
										$results['result'] = "NOK";
										$results['description'] = "INVALID PARENT";
										// Return error and end execution
										RSReturnArrayResults($results);
										exit();
									}
								}else{
									$results['result'] = "NOK";
									$results['description'] = "INVALID PARENT PROPERTY";
									// Return error and end execution
									RSReturnArrayResults($results);
									exit();
								}
							}

							//duplicate Item
							$newItemID = duplicateItem($itemTypeID, $itemID, $clientID);

							if($parentPropertyID!=0){
								//set new parent property only if parentPropertyID, otherwise will appear on root
								//if property is multiidentifier we have to keep other identifiers - sruiz: requirement removed on 2016-10-17
								$parentPropertyType = getPropertyType($parentPropertyID, $clientID);
								/*if (isMultiIdentifier($parentPropertyType)){
									$result=addIdentifier($parentID, $itemTypeID, $newItemID, $parentPropertyID, $clientID, $RSuserID);
									if($result==false){
										$results['description'] = 'ERROR UPDATING PARENT PROPERTY';
									}
								} else {*/
									$result=setPropertyValueByID($parentPropertyID,$itemTypeID,$newItemID,$clientID,$parentID,$parentPropertyType,$RSuserID,'0');
									//new item will have order '0' to be displayed first inside parent
									if($result<0){
										switch($result){
											case -1:
												$results['description'] = 'RECURSIVE COPY NOT ALLOWED';
												break;
											case -2:
												$results['description'] = 'INVALID USER';
												break;
											case -3:
												$results['description'] = 'ERROR COPYING ITEM';
												break;
										}
										$result=false;
									} else {
										$result=true;
									}
								/*}*/
							}

							if ($result==false) {
								//rollback transaction and exit foreach loop
								$mysqli->rollback();
								$results['result'] = 'NOK';
								break;
							}

						}else{
							$results['result'] = "NOK";
							$results['description'] = "NOT ENOUGH PERMISSIONS TO COPY ITEM";
						}
					}else{
						$results['result'] = "NOK";
						$results['description'] = "INVALID PARENT PROPERTY";
					}
				}else{
					$results['result'] = "NOK";
					$results['description'] = "INVALID ITEM";
				}
			}else{
				$results['result'] = "NOK";
				$results['description'] = "INVALID ITEMTYPE";
			}
		}
		if ($result) {
			//commit transaction
			$mysqli->commit();
			$results['result'] = 'OK';
		}
	}else{
		$results['result'] = "NOK";
		$results['description'] = "INVALID PARENT";
	}
}else{
	$results['result'] = "NOK";
	$results['description'] = "INVALID CLIENT";
}

// Return results
RSReturnArrayResults($results);
?>
