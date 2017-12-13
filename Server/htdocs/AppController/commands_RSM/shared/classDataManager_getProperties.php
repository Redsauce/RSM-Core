<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
	
// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$entries    = $GLOBALS['RS_POST']['entries' ];
$results    = array();

if ($entries != '') {
	$entries = explode(',', $entries);
	
	foreach ($entries as $entry) {
		// get entry data
		$entryArr = explode(';', $entry);
		
		// REMEMBER:
		// entryArr[0] = item ID
		// entryArr[1] = property ID
		// entryArr[2] = (optional) return data for file/image
		
		$pID = parsePID($entryArr[1], $clientID);
       
		if (isPropertyVisible($RSuserID, $pID, $clientID)) {		
			// save the property value
			if (count($entryArr == 3)) {
			    $results[$entryArr[0] . '.' . $entryArr[1]] = getItemDataPropertyValue ($entryArr[0], $pID, $clientID);
			} else
				$results[$entryArr[0] . '.' . $entryArr[1]] = getItemPropertyValue     ($entryArr[0], $pID, $clientID);
			
		}
	}
}

// Return results
RSReturnArrayResults($results);
?>