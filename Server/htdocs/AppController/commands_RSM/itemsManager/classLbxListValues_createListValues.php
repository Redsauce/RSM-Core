<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$listID = $GLOBALS['RS_POST']['listID'];

$values = array();
for ($i = 1; isset($GLOBALS['RS_POST']['value'.$i]); $i++) {
    $values[] = base64_decode($GLOBALS['RS_POST']['value'.$i]);
}

// remove duplicate from array
$values = array_unique($values);

// prepare the results array
$results = array();

foreach ($values as $value) {

    // assign an ID to the value
    $valueID = getNextIdentification('rs_property_values', 'RS_VALUE_ID', $clientID);

    // assign an order to the value
    $order = getGenericNext('rs_property_values', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_LIST_ID' => $listID));

    // build a query to insert the value
    $theQuery = 'INSERT INTO rs_property_values (RS_LIST_ID, RS_VALUE_ID, RS_VALUE, RS_CLIENT_ID, RS_ORDER) '.
                    'VALUES '.
                '('.$listID.','.$valueID.',"'.$value.'",'.$clientID.','.$order.')';

    // execute query
    $result = RSQuery($theQuery);

    if ($result) {
        $results[] = array('result' => 'OK', 'ID' => $valueID, 'value' => $value);
    } else {
        $results[] = array('result' => 'NOK', 'value' => $value);
    }
}

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
