<?php
//****************************************//
//latestAPK.php
//
//Description:
//    returns a file containing the latest APK file for PROD
//    environment in RIS
//
//params: none
//
//returns:
//    string: file binary stream
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

$clientID                 = "38"; // Roche has ID 38 in table rs_clients
$versionCodePropertyID    = "17"; // ID of the version code property from apk itemType
$environmentPropertyID    = "19"; // ID of the environment property from apk itemType (parent folder)
$environmentPropertyValue = "4" ; // ID of the parent item (parent folder) containing the apk item
$propertyID               = "14"; // Property 14 points to the APK executable file
$RStoken                  = "dc06787bfbf983dab6b83577ae6982d8"; // This token is managed in the API window

// Get item type
$itemTypeID = getItemTypeIDFromProperties(array($propertyID), $clientID);

// Construct filterProperties array
$filterProperties = array(
    array('ID' => $environmentPropertyID, 'value' => $environmentPropertyValue, 'mode' => 'IN')
);

// Construct returnProperties array
$returnProperties = array(
    array('ID' => $versionCodePropertyID, 'name' => "versionCode")
);

//Get itemID
$itemIDs = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, "versionCode");

//Get the last item ID from list
$lastItem = end($itemIDs);

//Check list not empty
if($lastItem !== false){
        $itemID = $lastItem["ID"];

        // Check token permissions
        if (!RShasREADTokenPermission($RStoken, $propertyID)) dieWithError(403);

        // Retrieve the file to download
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
                $nombre_descarga = base64_decode(rawurldecode(end($nombreSinExtension)));

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
                        if ($enable_file_cache) {
                                // Check if directory exists
                                if (!file_exists($directory)) {
                                        mkdir($directory, 0775, true);
                                }
                                error_log($file_path.",".$file_name.",".$extension.".");
                                saveFileCache($file_original, $file_path, $file_name, $extension);
                        }
                } else {
                        dieWithError(500);
                }
        }
}