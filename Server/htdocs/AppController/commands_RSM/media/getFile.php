<?php
//****************************************//
//getFile.php
//
//Description:
//    returns a file from the cache or media server
//
//params:
//        itemID: integer: id of the item containing the file to retrieve
//  propertyID: integer: id of the property of the item that contains the file
//         token:  string: authentication string
//returns:
//    file binary stream
//****************************************//

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
require_once "../utilities/RSMcacheManagement.php";

isset($GLOBALS["RS_GET" ]["itemID"    ]) ? $itemID     = $GLOBALS["RS_GET" ]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["propertyID"]) ? $propertyID = $GLOBALS["RS_GET" ]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["RStoken"   ]) ? $RStoken    = $GLOBALS["RS_GET" ]["RStoken"   ] : $RStoken = "";

$clientID   = RSclientFromToken($RStoken);

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) dieWithError(403);

$RSallowUncompressed = true;
$enable_file_cache   = true;

$directory = $RSfileCache . "/" . $clientID . "/" . $propertyID . "/";
$file_name = "file_" . $itemID;
$file_path = $directory . $file_name;

//check file in cache
$nombres_archivo = glob($file_path . "_*");

// Allow to request this document from JS libraries
header('Access-Control-Allow-Origin: *');

if (count($nombres_archivo) > 0) {

    // The file exists in cache
    $nombre_archivo = $nombres_archivo[0];
    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);
    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(end($nombreSinExtension));

    // The file was found in the cache. Return the cached file
    if (strtolower($extension) == "apk"){
        header('Content-type: application/vnd.android.package-archive');
    } else {
        header('Content-type: ' . mime_content_type($nombre_archivo));
    }
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');

    readfile($nombre_archivo);
} else {
    //file not in cache, get from media server
    // grab URL and receive file
    $file = getMediaFile($clientID,$itemID,$propertyID);

    if (isset($file["RS_DATA"])) {
        $file_original = $file["RS_DATA"];
        $file_name     = $file["RS_NAME"];
        $extension     = pathinfo($file_name, PATHINFO_EXTENSION);

        // Return the original file
        if (strtolower($extension) == "apk"){
            header('Content-type: application/vnd.android.package-archive');
        } else {
            header("Content-type: application/" . $extension);
        }
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        echo $file_original;
        saveFileCache($file_original, $file_path, $file_name, $extension);
    } else {
        dieWithError(500);
    }
}

?>
