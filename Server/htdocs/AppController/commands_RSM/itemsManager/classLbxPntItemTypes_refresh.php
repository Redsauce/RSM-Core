<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$its_props = explode(',', $GLOBALS['RS_POST']['itemTypesProps']);
$pointerItemID = $GLOBALS['RS_POST']['pointerItemID'];

// prepare results array
$finalResults = array();

foreach ($its_props as $it_prop) {
	
	// get item type and properties
	$arr = explode(';', $it_prop);
	
	// item type
	$itemTypeID = $arr[0];
	
	// properties
	$pointerPropertyIDs = explode(',', base64_decode($arr[1]));
	
	
	// get main property ID
	$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

	// save item type ID
	$finalResults[] = array('itemTypeID' => $itemTypeID);
	
	// reset partialResults array
	$partialResults = array();
	
	foreach ($pointerPropertyIDs as $pointerPropertyID) {
		
		// get property type
		$propertyType = getPropertyType($pointerPropertyID, $clientID);
		
		if (isSingleIdentifier($propertyType)) {
			$filter = array(array('ID' => $pointerPropertyID, 'value' => $pointerItemID));
		} else {
			$filter = array(array('ID' => $pointerPropertyID, 'value' => $pointerItemID, 'mode' => 'IN'));
		}
	
		// get items
		$items = IQ_getFilteredItemsIDs(
			$itemTypeID,
			$clientID,
			$filter,
			array(array('ID' => $mainPropertyID, 'name' => 'mainValue')),
			'mainValue'
		);
	
		while ($item = $items->fetch_assoc()) {
			for ($i = 0; $i < count($partialResults); $i++) {
				if ($item['ID'] == $partialResults[$i]['ID']) {
					// item already added
					break;
				}
			}
			
			if ($i == count($partialResults)) {
				// add to the results
				$partialResults[] = $item;
			}
		}	
	}
	
	// append to the final results
	$finalResults = array_merge($finalResults, $partialResults);
}
	
// Return data			
RSReturnArrayQueryResults($finalResults);
?>