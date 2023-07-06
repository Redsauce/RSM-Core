<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$appListID = $GLOBALS['RS_POST']['appListID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$appListValues = getAppListValues($appListID);

$results = array();
foreach ($appListValues as $value) {
    $clientValueIDRelated = getClientListValueIDRelatedWith($value['valueID'], $clientID);
    if ($clientValueIDRelated == '0') {
        $related = '0';
    } else {
        $related = '1';
    }
    $results[] = array('ID' => $value['valueID'], 'value' => $value['value'], 'related' => $related);
}

// And write XML Response back to the application
RSreturnArrayQueryResults($results);
