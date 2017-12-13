<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];

$paymentMethodPropertyID = getClientPropertyID_RelatedWith_byName($definitions['operationPayMethod'], $clientID);
$paymentMethodList = getPropertyList($paymentMethodPropertyID, $clientID);

$listValues = array();
if ($paymentMethodList != false) {
    $listValues = getListValues($paymentMethodList["listID"], $clientID);
}

// And write XML Response back to the application
RSReturnArrayQueryResults($listValues);
?>
