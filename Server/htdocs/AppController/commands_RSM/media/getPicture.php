<?php
//****************************************//
//getPicture.php
//
//Description:
//    returns a picture from the cache or media server
//
//params:
//        itemID: integer: id of the item containing the file to retrieve
//  propertyID: integer: id of the property of the item that contains the file
//         token:  string: authentication string
//returns:
//    picture binary stream
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

$directory = $RSfileCache . "/" . $clientID . "/" . $propertyID . "/";
$file_name = "img_" . $itemID;
$file_path = $directory . $file_name;

//check file in cache
$nombres_archivo = glob($file_path . "_*");

//Check if cached images are resized versions of original file with format like img_84_250_320_h_Rm90byBQZXJmaWwuanBn.jpg
for ($i=count($nombres_archivo)-1;$i>=0;$i--) {
    if (preg_match("/img_\d+_.*?_.*?_/i",$nombres_archivo[$i])) unset($nombres_archivo[$i]);
}
$nombres_archivo = array_values($nombres_archivo);

// Allow to request this document from JS libraries
header('Access-Control-Allow-Origin: *');

if ($enable_image_cache && count($nombres_archivo) > 0) {

    // The file exists in cache
    $nombre_archivo = $nombres_archivo[0];
    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);
    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(end($nombreSinExtension));

    // The file was found in the cache. Return the cached file
    header('Content-type: ' . mime_content_type($nombre_archivo));
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
        header("Content-type: application/" . $extension);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        echo $file_original;
        if ($enable_image_cache) saveFileCache($file_original, $file_path, $file_name, $extension);
    } else {
        dieWithError(500);
    }
}

?>
