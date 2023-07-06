<?php
//****************************************//
//api_getFile.php
//
//Description:
//    returns a file from the cache or the database
//
//params:
//        itemID: integer: id of the item containing the file to retrieve
//  propertyID: integer: id of the property of the item that contains the file
//         token:  string: authentication string
//returns:
//    string: picture binary stream
//****************************************//

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
require_once "../utilities/RSMcacheManagement.php";
require_once "./api_headers.php";

isset($GLOBALS["RS_POST"]["clientID"  ]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"  ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["itemID"    ]) ? $itemID     = $GLOBALS["RS_GET" ]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["propertyID"]) ? $propertyID = $GLOBALS["RS_GET" ]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["RStoken"   ]) ? $RStoken    = $GLOBALS["RS_GET" ]["RStoken"   ] : $RStoken = "";

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) {
    dieWithError(403);
}

$directory = $RSfileCache . "/" . $clientID . "/" . $propertyID . "/";
$file_name = "file_" . $itemID;
$file_path = $directory . $file_name;

//check file in cache
$nombres_archivo = glob($file_path . "_*");

if ($enable_file_cache && !empty($nombres_archivo)) {

    // The file exists in cache
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
    //file not in cache or using cache not allowed, generate new
    $file          = getFile($clientID, $propertyID, $itemID);
    if ($file) {
        $file_original = $file["RS_DATA"];
        $file_name     = $file["RS_NAME"];
        $extension     = pathinfo($file_name, PATHINFO_EXTENSION);

        // If file data is empty but the size field is > 0 then the file is in media server
        if ($file["RS_SIZE"] > 0 && $file_original == '') {
            $fileData = getMediaFile($clientID, $itemID, $propertyID);
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
        if ($enable_file_cache) {
            saveFileCache($file_original, $file_path, $file_name, $extension);
        }
    } else {
        dieWithError(500);
    }
}

