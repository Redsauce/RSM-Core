<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

$clientID         = $GLOBALS['RS_POST']['clientID'];
$parentItemTypeID = $GLOBALS['RS_POST']['parentItemTypeID'];

if ($GLOBALS['RS_POST']['allowedItemTypeIDs']!=""){
	$allowedItemTypes = explode(",", $GLOBALS['RS_POST']['allowedItemTypeIDs']);
} else {
	$allowedItemTypes = array();
}

$results = array();

if(count($allowedItemTypes) == 0 || $allowedItemTypes[0] == ""){
	// Not allowed itemtypes, get all
	$theQuery = "SELECT `RS_ITEMTYPE_ID` as 'ID' FROM `rs_item_types` WHERE `RS_CLIENT_ID`='".$clientID."' ORDER BY `RS_ORDER`";

	// Query the database
	$res = RSquery($theQuery);

    if ($res) {
	   while($row=$res->fetch_assoc()) $allowedItemTypes[]=$row['ID'];
    }

}

$destinationItemTypes=$allowedItemTypes;

//get parent ItemType MainProperty ID and Type for treePath root level
$parentItemTypeMainPropertyID=getMainPropertyID($parentItemTypeID, $clientID);
$parentItemTypeMainPropertyType=getPropertyType($parentItemTypeMainPropertyID, $clientID);

foreach($destinationItemTypes as $destinationItemTypeID){
	//get path
	$treePath=array();
	getTreePath($clientID,$treePath,array(array('itemTypeID'=>$parentItemTypeID,'mainPropertyID'=>$parentItemTypeMainPropertyID,'mainPropertyType'=>$parentItemTypeMainPropertyType)),$destinationItemTypeID,$allowedItemTypes,10);
	
	//get path for item
	foreach($treePath as $path){
		$tempPath=array();

		for($i=0;$i<count($path);$i++){
			$tempPath[]=array('nodeID'=>'1','nodeItemType'=>$path[$i]['itemTypeID'],'parentID'=>(($i>0)?('1'):('0')),'parentItemType'=>(($i>0)?($path[$i-1]['itemTypeID']):($parentItemTypeID)),'name'=>getClientItemTypeName($path[$i]['itemTypeID'],$clientID),'parentPropertyID'=>(($i>0)?($path[$i]['propertyID']):('0')),'childs'=>(($i<count($path)-1)?('1,'.$path[$i+1]['itemTypeID']):('')));
		}

		//remove first itemtype if not recursive
		if($path[0]['recursive']==0){
			array_shift($tempPath);
            if(count($tempPath)>0) $tempPath[0]['parentID']='0';
		}

		$results=combineItemPaths($results,$tempPath);
	}
}
// Loop all itemtypes and check user has permision to view it
$notAllowedItemtypes=array();
foreach($results as $result){
	if ($result['parentPropertyID']!='0' && !isPropertyVisible($RSuserID, $result['parentPropertyID'], $clientID)){
		if (!in_array($result['nodeItemType'],$notAllowedItemtypes)) $notAllowedItemtypes[] = $result['nodeItemType'];
	}
}
 // Remove all not allowed itemtypes and all its descendants because don't have permissions to view them
 foreach($results as $key => $result){
	 if (in_array($result['nodeItemType'],$notAllowedItemtypes)){
		 unset($results[$key]);
	 } elseif (in_array($result['parentItemType'],$notAllowedItemtypes)) {
		 // As this itemtype is child of a removed one, we add it to notAllowedItemtypes array (for removing also its childs) and remove it
		 if (!in_array($result['nodeItemType'],$notAllowedItemtypes)) $notAllowedItemtypes[] = $result['nodeItemType'];
		 unset($results[$key]);
	 }
 }

array_unshift($results, array("result"=>"OK"));

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
?>
