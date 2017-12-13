<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

function deleteDescendants($clientID,$parentID,$parentItemTypeID,$allowedItemPath,$userID,$counter=100){

	$counter--;
	if(count($allowedItemPath)>0&&$counter>=0){
		//extract(remove) and split first itemType/property in path
		$allowedItemType=explode(",",array_shift($allowedItemPath));
		//check recursive itemtype
		$descendants=getDescendantsLevel($clientID,$allowedItemType[0],array($allowedItemType[0]));
		if(count(getUserVisiblePropertiesIDs($allowedItemType[0],$clientID,$userID))>0){
			//the user can delete the items
			//check base level recursive
			if($allowedItemType[1]==0){
				//apply recursively if recursive itemtype
				if(count($descendants) > 0){
					if(!deleteDescendants($clientID, $parentID, $allowedItemType[0], array_merge(array($allowedItemType[0].",".$descendants[0]['propertyID']), $allowedItemPath), $userID, $counter)){
						//not enough permissions to delete descendants
						return false;
					}
				}
				//recursive delete descendants
				if(!deleteDescendants($clientID, $parentID, $allowedItemType[0], $allowedItemPath, $userID, $counter)){
					//not enough permissions to delete descendants
					return false;
				}
			}else{
				// build filter
				$filterProperties = array();
				$filterProperties[] = array('ID' => $allowedItemType[1], 'value' => $parentID);

				$returnProperties = array();

				// get items pertaining to the parent passed
				$result = getFilteredItemsIDs($allowedItemType[0], $clientID, $filterProperties, $returnProperties);

				foreach($result as $item) {
					//apply recursively if recursive itemtype
					if(count($descendants) > 0 && array_key_exists('recursivePropertyID',$descendants[0])){
						if(!deleteDescendants($clientID, $item['ID'], $allowedItemType[0], array_merge(array($allowedItemType[0].",".$descendants[0]['recursivePropertyID']), $allowedItemPath), $userID, $counter)){
							//not enough permissions to delete descendants
							return false;
						}
					}
					//recursive delete descendants
					if(deleteDescendants($clientID, $item['ID'], $allowedItemType[0], $allowedItemPath, $userID, $counter)){
						//finally delete the item
						deleteItem($allowedItemType[0], $item['ID'], $clientID);
					}else{
						//not enough permissions to delete descendants
						return false;
					}
				}
			}
		}else{
			//not enough permissions to delete
			return false;
		}
	}
	//if no error return true
	return true;
}

function relocateDescendants($clientID,$parentID,$parentItemTypeID,$newParentID,$allowedItemTypes,$userID){
	$descendants=getDescendantsLevel($clientID,$parentItemTypeID,$allowedItemTypes);

	foreach($descendants as $descendant){
		if(isPropertyVisible($userID,$descendant['propertyID'],$clientID)){
			// build filter
			$filterProperties = array();
			$filterProperties[] = array('ID' => $descendant['propertyID'], 'value' => $parentID);

			$returnProperties = array();

			// get items pertaining to the parent passed
			$result = IQ_getFilteredItemsIDs($descendant['itemTypeID'], $clientID, $filterProperties, $returnProperties);

            if ($result) {
                while ($item = $result->fetch_assoc()) {
                    //update descendant's parent
                    $res=setPropertyValueByID($descendant['propertyID'],$descendant['itemTypeID'],$item['ID'],$clientID,$newParentID,'',$userID);
                    if ($res<0) return $res; //error while updating
                }
			}
		}else{
			//not enough permissions to update
			return -4;
		}
	}
	//if no error return true
	return 0;
}

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$itemIDs = explode(",",$GLOBALS['RS_POST']['itemIDs']);
if(isset($_POST['recursiveDelete'])){
	$recursiveDelete = $GLOBALS['RS_POST']['recursiveDelete'];
	if($GLOBALS['RS_POST']['allowedItemPaths']!=""){
		$allowedItemPaths = explode(";;",$GLOBALS['RS_POST']['allowedItemPaths']);
	}else{
		$allowedItemPaths = array();
	}
}
if(isset($_POST['newParentID'])){
	$newParentID = $GLOBALS['RS_POST']['newParentID'];
}
if(isset($_POST['recursiveDelete'])&&isset($_POST['newParentID'])){
	$results['result'] = "NOK";
	$results['description'] = "CANT DELETE AND MOVE ITEMS AT THE SAME TIME";
}else{
	if($clientID!=0&&$clientID!=""){
		if ($itemTypeID!=0&&$itemTypeID != ''){
			if (count($itemIDs)>0&&$itemIDs[0]!=''){
				if(count(getUserVisiblePropertiesIDs($itemTypeID,$clientID,$RSuserID))>0){
					//the user can delete the items
					//begin transaction
					$mysqli->begin_transaction();


					if(isset($_POST['recursiveDelete'])&&$recursiveDelete==1){
						foreach($itemIDs as $itemID){
							foreach($allowedItemPaths as $allowedItemPath){
								$result=deleteDescendants($clientID,$itemID,$itemTypeID,explode(";",$allowedItemPath),$RSuserID);
								if(!$result){
									//rollback transaction
									$mysqli->rollback();
									$results['result'] = 'NOK';
									$results['description'] = 'NOT ENOUGH PERMISSIONS TO DELETE DESCENDANTS';
									break;
								}
							}
						}
						//no errors in recursive deleting, continue with deleting
						$result=true;

					}elseif(isset($_POST['newParentID'])&&$newParentID!=''){
						foreach($itemIDs as $itemID){
							$result=relocateDescendants($clientID,$itemID,$itemTypeID,$newParentID,array(),$RSuserID);
							if($result<0){
								//rollback transaction
								$mysqli->rollback();
								$results['result'] = 'NOK';
								switch($result){
									case -1:
										$results['description'] = 'RECURSIVE MOVE NOT ALLOWED';
										break;
									case -2:
										$results['description'] = 'INVALID USER';
										break;
									case -3:
										$results['description'] = 'ERROR MOVING DESCENDANTS';
										break;
									case -4:
										$results['description'] = 'NOT ENOUGH PERMISSIONS TO MOVE DESCENDANTS';
										break;
								}
								$result=false;
								break;
							}
						}
						//no errors relocating, continue with deleting
						$result=true;

					}else{
						$hasChilds=0;
						//get all descendants of this itemtype
						$subDescendants=getDescendantsLevel($clientID,$itemTypeID,array());

						foreach($itemIDs as $itemID){
							foreach($subDescendants as $subDescendant){
								// build filter
								$filterProperties = array();
								$filterProperties[] = array('ID' => $subDescendant['propertyID'], 'value' => $itemID);

								$returnProperties = array();

								// get items pertaining to the parent passed
								$subResult = getFilteredItemsIDs($subDescendant['itemTypeID'], $clientID, $filterProperties, $returnProperties);
								if(count($subResult)>0){
									$hasChilds=1;
									break;
								}
							}
							if($hasChilds==1){
								break;
							}
						}
						if($hasChilds==1){
							//rollback transaction
							$result=false;
							$mysqli->rollback();
							$results['result'] = 'NOK';
							$results['description'] = 'RECURSIVE DELETION REQUIRED';
						}else{
							//no childs, continue with deleting
							$result=true;
						}
					}

					if($result){
						foreach($itemIDs as $itemID){
							deleteItem($itemTypeID,$itemID,$clientID);
						}

						//commit transaction
						$mysqli->commit();
						$results['result'] = 'OK';
					}
				}else{
					$results['result'] = 'NOK';
					$results['description'] = 'NOT ENOUGH PERMISSIONS';
				}
			}else{
				$results['result'] = 'NOK';
				$results['description'] = 'INVALID ITEMTYPE';
			}
		}else{
			$results['result'] = 'NOK';
			$results['description'] = 'INVALID ITEM';
		}
	}else{
		$results['result'] = "NOK";
		$results['description'] = "INVALID CLIENT";
	}
}



// Return results
RSReturnArrayResults($results);
?>
