<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// init arrays with comma separated posted values
$idList = explode(',', $GLOBALS['RS_POST']['ids']);

for ($i = 0; $i < count($idList); $i++) {
    // reorder the element
    $theQuery = 'UPDATE rs_item_types SET RS_ORDER = '.($i+1).' WHERE (RS_ITEMTYPE_ID = '.$idList[$i].' AND RS_CLIENT_ID = '. $GLOBALS['RS_POST']['clientID'].')';

    // execute query
    if (!RSQuery($theQuery)) {
        // return NOK
        $results['result'] = "NOK";

        // Write XML Response back to the application
        RSReturnArrayResults($results);
        exit;
    }
}
$results['result'] = "OK";

// And write XML Response back to the application
RSReturnArrayResults($results);
