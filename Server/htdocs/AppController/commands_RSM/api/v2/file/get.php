<?php
//***************************************************************************************
// Description:
//    Returns a file from the cache or the database
//  Query params:
//      ID: integer: id of the item containing the file to retrieve
//      propertyID: integer: id of the property of the item that contains the file
//
//***************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";

setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');
require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../../utilities/RSMtokensManagement.php";
require_once "../../../utilities/RSMcacheManagement.php";
require_once "../../api_headers.php";

// Definitions and validations
$parameters = getRequestParams();
verifyRequestParams($parameters);
$RStoken =  getRStoken();
$clientID = getClientID();
$propertyID = $parameters["propertyID"];
$ID = $parameters["ID"];

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) {
    if ($RSallowDebug) returnJsonMessage(403, "Token has no permissions to get this file");
    else returnJsonMessage(403, "");
}
// Check if asked property is file
if (getPropertyType($propertyID, $clientID) != 'file') {
    if ($RSallowDebug) returnJsonMessage(404, "Property is not a file");
    else returnJsonMessage(404, "");
}

// create file path
$directory = $RSfileCache . "/" . $clientID . "/" . $propertyID . "/";
$file_name = "file_" . $ID;
$file_path = $directory . $file_name;

$nombres_archivo = glob($file_path . "_*");

// verify if the file is in the cache
if ($enable_file_cache && count($nombres_archivo) > 0) {

    // the file exists in cache
    $nombre_archivo = $nombres_archivo[0];
    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);

    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(rawurldecode(end($nombreSinExtension)));

    // The file was found in the cache. Return the cached file
    if (strtolower($extension) == "apk") {
        header('Content-type: application/vnd.android.package-archive');
    } else {
        header('Content-type: ' . mime_content_type($nombre_archivo));
    }
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');

    readfile($nombre_archivo);
} else {
    // the file is not in cache or using cache is not allowed, generate new

    $file = getFile($clientID, $propertyID, $ID);

    if ($file) {
        $file_original = $file["RS_DATA"];
        $file_name     = $file["RS_NAME"];
        $extension     = pathinfo($file_name, PATHINFO_EXTENSION);

        // If file data is empty but the size field is > 0 then the file is in media server
        if ($file["RS_SIZE"] > 0 && $file_original == '') {
            $fileData = getMediaFile($clientID, $ID, $propertyID);
            $file_original = $fileData['RS_DATA'];
        }

        // Return the original file
        if (strtolower($extension) == "apk") {
            header('Content-type: application/vnd.android.package-archive');
        } else {
            header("Content-type: application/" . $extension);
        }
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        echo $file_original;
        if ($enable_file_cache) saveFileCache($file_original, $file_path, $file_name, $extension);
    } else {
        returnJsonMessage(200, "");
    }
}

//validate params sent are the ones needed
function verifyRequestParams($parameters)
{
    checkParamsContainsID($parameters);
    checkParamsContainsPropertyID($parameters);
}