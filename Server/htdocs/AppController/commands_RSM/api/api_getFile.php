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

isset($GLOBALS["RS_POST"]["clientID"  ]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"  ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["itemID"    ]) ? $itemID     = $GLOBALS["RS_GET" ]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["propertyID"]) ? $propertyID = $GLOBALS["RS_GET" ]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_GET" ]["RStoken"   ]) ? $RStoken    = $GLOBALS["RS_GET" ]["RStoken"   ] : $RStoken = "";

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
    //file not in cache, generate new
    $file          = getFile($clientID, $propertyID, $itemID);
    if ($file) {
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

/**
 * Save file in cache directory
 */
function saveFileCache($file_original, $path, $name, $extension) {
    global $enable_file_cache;
    global $directory;

    if ($enable_file_cache) {
        // Check if directory exists
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = $path . "_" . base64_encode($name) . "." . $extension;

        // Check folder exists or create it otherwise
        $dirname = dirname($file);
        if (!is_dir($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                RSError("api_getFile: Could not create cache directory");
            }
        }

        $fh = fopen($file, "w");
        if ($fh) {
            fwrite($fh, $file_original);
            fclose($fh);
        } else {
            RSError("api_getFile: Could not create cache file");
        }

        return 0;
    }
}
?>
