<?php

function setGlobalVariable() {

}

function getGlobalVariableValue($variableName, $clientID) {
    // Escapar el nombre de la variable para prevenir inyección SQL
    $escapedVariableName = mysqli_real_escape_string($GLOBALS['mysqli'], $variableName);

    // Ejecutar consulta
    $query = 'SELECT RS_VALUE AS "value", RS_IMAGE AS "image"
              FROM rs_globals
              WHERE RS_CLIENT_ID = ' . intval($clientID) . '
              AND RS_NAME = "' . $escapedVariableName . '"';
    $results = RSquery($query);

    // Verificar si la consulta devolvió resultados
    if (!$results || $results->num_rows === 0) {
        return "";
    }

    $result = $results->fetch_assoc();

    // Validar que los índices existen
    if (isset($result['image']) && $result['image'] == '1') {
        // Convertir valor binario a hexadecimal
        return isset($result['value']) ? bin2hex($result['value']) : "";
    }

    return $result['value'] ?? ""; // Devolver valor o cadena vacía si no existe
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
