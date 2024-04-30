<?php

function setGlobalVariable() {

}

function getGlobalVariableValue($variableName, $clientID) {
	// execute query
	$results = RSquery('SELECT RS_VALUE AS "value", RS_IMAGE AS "image" FROM rs_globals WHERE RS_CLIENT_ID = '. $clientID . ' AND RS_NAME = "' . $variableName . '"');

	if (!$results) return "";

	$result = $results->fetch_assoc();

	if ($result['image'] == '1') {
					// convert binary value to hexadecimal
					return bin2hex($result['value']);
	}

	return $result['value'];
}

function getGlobalVariables($clientID) {
	// execute query
	$result = RSquery('SELECT RS_NAME AS "name", RS_VALUE AS "value", RS_IMAGE AS "image" FROM rs_globals WHERE RS_CLIENT_ID = '.$clientID.' ORDER BY RS_NAME');

	$results = array();

	if (is_bool($result) && !$result) {
        return $results;
    }

    if ($result->num_rows == 0) {
        return $results;
    }

	while ($row = $result->fetch_assoc()) {
		if ($row['image'] == '1') {
			// convert binary value to hexadecimal
			$row['value'] = bin2hex($row['value']);
		}

		// add entry to the results
		$results[] = $row;
	}

	return $results;
}

?>
