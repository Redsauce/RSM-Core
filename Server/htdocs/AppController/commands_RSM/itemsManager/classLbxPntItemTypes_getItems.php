<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions

$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$pointerPropertyIDs = explode(',', $GLOBALS[$cstRS_POST][$cstPointerPropertyIDs]);
$pointerItemID = $GLOBALS[$cstRS_POST][$cstPointerItemID];

// get main property ID
$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

// prepare results array
$results = array();

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
		for ($i = 0; $i < count($results); $i++) {
			if ($item['ID'] == $results[$i]['ID']) {
				// item already added
				break;
			}
		}
		
		if ($i == count($results)) {
			// add to the results
			$results[] = $item;
		}
	}	
}
	
// Return data			
RSReturnArrayQueryResults($results);
?>