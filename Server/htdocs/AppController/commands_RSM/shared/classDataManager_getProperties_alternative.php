<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID   = $GLOBALS['RS_POST']['clientID'];
$entries    = $GLOBALS['RS_POST']['entries' ];
$results    = array();

if (strlen($entries) > 0) {
	$comma = ',';
	$lengthComma = strlen($comma);
	$semicolon = ';';
	$lengthSemicolon = strlen($semicolon);

	$lastOcurrence = 0;
	$commaPositions = array();
	while (($lastOcurrence = strpos($entries, $comma, $lastOcurrence)) !== false) {
		$commaPositions[] = $lastOcurrence;
		$lastOcurrence = $lastOcurrence + $lengthComma;
	}

	$index = 0;
	$lastOcurrence = 0;
	foreach ($commaPositions as $commaPosition) {
		$semicolonPositions = array();
		while (($lastOcurrence = strpos($entries, $semicolon, $lastOcurrence)) !== false && $lastOcurrence < $commaPosition ) {
			$semicolonPositions[] = $lastOcurrence;
			$lastOcurrence = $lastOcurrence + $lengthSemicolon;
		}
		$lastOcurrence = $commaPosition + $lengthComma;

		// REMEMBER:
		// entryArr[0] = item ID
		// entryArr[1] = property ID
		// entryArr[2] = (optional) return data for file/image
		$entryArr0 = substr($entries, $index, $semicolonPositions[0] - 1);
		$index = $semicolonPositions[0] + $lengthSemicolon;
		$entryArr1 = substr($entries, $index, $semicolonPositions[1] - 1);
		$index = $lastOcurrence;

		$pID = parsePID($entryArr1, $clientID);

		if (isPropertyVisible($RSuserID, $pID, $clientID)) {
			// save the property value
			$value = NULL;
			if (count($semicolonPositions) == 2) {
				$value = getItemDataPropertyValue ($entryArr[0], $pID, $clientID);
			} else {
				$value = getItemPropertyValue     ($entryArr[0], $pID, $clientID);
			}
			$results[$entryArr[0] . '.' . $entryArr[1]] = $value;
		}
	}
}

// Return results
RSReturnArrayResults($results);
?>