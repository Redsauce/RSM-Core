<?php
//rsm1.redsauce.net/AppController/commands_RSM/media/storeFile.php?itemID=1425&propertyID=825&RStoken=RSM-storeOnline1bd7acd5cc20d6810
//test values
//$clientID   = "";
//$itemID     = "";
//$propertyID = "";
//$RStoken    =  "";
//$GLOBALS["RS_GET" ]["RStoken"   ] = $RStoken;

// Clean GET data in order to avoid SQL injections
$search = array("'", "\"");
$replace = array("", "");

foreach ($_GET as $key => $value) {
    $GLOBALS["RS_GET"][$key] = str_replace($search, $replace, $value);
}

require_once "../utilities/RStools.php";
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMtokensManagement.php";
require_once "../utilities/RSMmediaManagement.php";

isset($GLOBALS["RS_GET" ]["itemID"    ]) ? $itemID     = $GLOBALS["RS_GET" ]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["propertyID"]) ? $propertyID = $GLOBALS["RS_GET" ]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["RStoken"   ]) ? $RStoken    = $GLOBALS["RS_GET" ]["RStoken"   ] : $RStoken = "";
$clientID   = RSclientFromToken($RStoken);

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) dieWithError(403);

$results= array();
//file query
$file          = getFile($clientID, $propertyID, $itemID);
if ($file) {
    $file_data = $file["RS_DATA"];
    $file_name = $file["RS_NAME"];

    $results = setMediaFile($clientID,$itemID,$propertyID,$file_data,$file_name);
} else {
    $results['result'     ] = "NOK";
    $results['description'] = "File not found: clientID=" . $clientID . ', itemID=' . $itemID . ', propertyID=' . $propertyID ;
}

return $results;
?>
