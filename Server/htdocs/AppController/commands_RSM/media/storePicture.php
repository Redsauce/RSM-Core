<?php

// Clean GET data in order to avoid SQL injections
$search = array("'", "\"");
$replace = array("", "");

foreach ($_GET as $key => $value) {
    $GLOBALS["RS_GET"][$key] = str_replace($search, $replace, $value);
}

require_once "../utilities/RSconfiguration.php";
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
$file          = getImage($clientID, $propertyID, $itemID);
if ($file) {
    $file_data = $file["RS_DATA"];
    $file_name = $file["RS_NAME"];
    if ($file_data != "") {
        $results = setMediaFile($clientID,$itemID,$propertyID,$file_data,$file_name);
    }  else {
        $results['result'     ] = "NOK";
        $results['description'] = "Can't upload empty image: clientID=" . $clientID . ', itemID=' . $itemID . ', propertyID=' . $propertyID ;
    }
} else {
    $results['result'     ] = "NOK";
    $results['description'] = "Image not found: clientID=" . $clientID . ', itemID=' . $itemID . ', propertyID=' . $propertyID ;
}

return $results;
?>
