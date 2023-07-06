<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$listID = $GLOBALS['RS_POST']['listID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$listValues = getListValues($listID, $clientID);

$results = array();
foreach ($listValues as $value) {
    $appValueIDRelated = getAppListValueID_RelatedWith($value['valueID'], $clientID);
    if ($appValueIDRelated == '0') {
        $related = '0';
    } else {
        $related = '1';
    }
    $results[] = array('ID' => $value['valueID'], 'value' => $value['value'], 'related' => $related);
}

// And write XML Response back to the application
RSreturnArrayQueryResults($results);
