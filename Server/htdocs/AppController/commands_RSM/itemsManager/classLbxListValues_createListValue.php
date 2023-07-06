<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0) {

    // check if the value already exist
    $theQuery_valueExists = 'SELECT RS_VALUE_ID FROM rs_property_values WHERE RS_CLIENT_ID=' . $GLOBALS['RS_POST']['clientID'] . ' AND RS_LIST_ID=' . $GLOBALS['RS_POST']['listID'] . ' AND RS_VALUE= "' . base64_decode($GLOBALS['RS_POST']['value']) . '"';

    $result = RSquery($theQuery_valueExists);

    if ($result->num_rows == 0) {

        $theQuery = "INSERT INTO rs_property_values (RS_VALUE_ID, RS_LIST_ID, RS_CLIENT_ID, RS_VALUE, RS_ORDER) VALUES (" . getNextIdentification('rs_property_values', 'RS_VALUE_ID', $GLOBALS['RS_POST']['clientID']) . "," . $GLOBALS['RS_POST']['listID'] . "," . $GLOBALS['RS_POST']['clientID'] . ", '" . base64_decode($GLOBALS['RS_POST']['value']) . "', " . getGenericNext('rs_property_values', 'RS_ORDER', array("RS_CLIENT_ID" => $GLOBALS['RS_POST']['clientID'], "RS_LIST_ID" => $GLOBALS['RS_POST']['listID'])) . ")";

        if (isset($GLOBALS['RS_POST']['RSdebug']) && $GLOBALS['RS_POST']['RSdebug']) {
            echo $theQuery;
        }

        $result = RSquery($theQuery);

        $results['result'] = "OK";
        $results['ID'] = getLastIdentification('rs_property_values', 'RS_VALUE_ID', $GLOBALS['RS_POST']['clientID']);
        $results['value'] = base64_decode($GLOBALS['RS_POST']['value']);
    } else {
        $results['result'] = "NOK2";
        $results['value'] = base64_decode($GLOBALS['RS_POST']['value']);
    }
} else {
    $results['result'] = "NOK1";
}

// And write XML Response back to the application
RSreturnArrayResults($results);
