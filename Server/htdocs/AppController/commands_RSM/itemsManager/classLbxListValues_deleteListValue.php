<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMidentificationFunctions.php";
require_once "../utilities/RSMlistsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$valueID = $GLOBALS['RS_POST']['valueID'];
$listID = $GLOBALS['RS_POST']['listID'];

if (getAppListValueID_RelatedWith($valueID, $clientID) == '0') {

	// value not related... remove it
	RSQuery("DELETE FROM rs_property_values WHERE RS_VALUE_ID = ".$valueID." AND RS_LIST_ID = ".$listID." AND RS_CLIENT_ID = ".$clientID);	

	$results['result'] = 'OK';
	$results['ID'] = $valueID;

} else {

	// value related... can't remove it
	$results['result'] = 'NOK';

}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
