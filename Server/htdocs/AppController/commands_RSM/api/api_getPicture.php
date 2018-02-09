<?php
//****************************************//
//getScaledPicture.php
//
//Description:
//    returns a picture with selected size
//
//params:
//        itemID: integer: id of the item containing the image to retrieve
//  propertyID: integer: id of the property of the item that contains the picture
//         token:  string: authentication string
//             w: integer: selected width (same as original by default)
//             h: integer: selected height (same as original by default)
//           adj:  string: adjust scale to selected dimension (s by default):
//                      (s(how all: the image is scaled proportionally to fit completely inside passed dimensions leaving blank space if needed)|
//                       f(ill all: the image is scaled proportionally to fit lesser dimmension, centered and cropped to fit the other dimension)|
//                       w(idth: the image is centered and scaled proportionally to fit the passed width and cropped in height if needed)|
//                       h(eight: the image is centered and scaled proportionally to fit the passed height and cropped in width if needed)|
//                       d(eform: the image is scaled unproportionally to fit completely both passed dimensions)|
//                       c(rop: the image is centered and cropped if it's bigger than passed dimensions without scaling))
//returns:
//    string: picture binary stream
//****************************************//

// Clean GET data in order to avoid SQL injections
$search  = array("'", "\"");
$replace = array("" , ""  );

foreach ($_GET as $key => $value) {
    $GLOBALS["RS_GET"][$key] = str_replace($search, $replace, $value);
}

require_once "../utilities/RStools.php";
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMtokensManagement.php";
require_once "../utilities/RSMcacheManagement.php";

isset($GLOBALS["RS_GET"]["itemID"]    ) ? $itemID     = $GLOBALS["RS_GET"]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_GET"]["propertyID"]) ? $propertyID = $GLOBALS["RS_GET"]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_GET"]["RStoken"]   ) ? $RStoken    = $GLOBALS["RS_GET"]["RStoken"   ] : $RStoken = '';
isset($GLOBALS["RS_GET"]["adj"]       ) ? $adj        = $GLOBALS["RS_GET"]["adj"       ] : $adj = 's';

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) dieWithError(403);

// Allow cross origin get svg
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

isset($GLOBALS["RS_GET"]["w"]) ? $w = $GLOBALS["RS_GET"]["w"] : $w = "";
isset($GLOBALS["RS_GET"]["h"]) ? $h = $GLOBALS["RS_GET"]["h"] : $h = "";
$clientID = $GLOBALS["RS_POST"]["clientID"];

$directory = $RSimageCache . "/" . $clientID . "/" . $propertyID . "/";
$image_name = "img_" . $itemID . "_" . $w . "_" . $h . "_" . $adj;
$image_string = $directory . $image_name;

//check image in cache
$nombres_archivo = glob($image_string . "_*");

// Allow to request this document from JS libraries
header('Access-Control-Allow-Origin: *');

if ($enable_image_cache && count($nombres_archivo) > 0) {
    // The image exists in cache
    $nombre_archivo = $nombres_archivo[0];
    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);
    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(end($nombreSinExtension));

    // The file was found in the cache. Return the cached file
    header('Content-type: ' . mime_content_type($nombre_archivo));
    header('Content-Disposition: inline; filename="' . $nombre_descarga . '"');

    readfile($nombre_archivo);

} else {
    //check base image in cache
    $nombres_archivo = glob($directory . "img_" . $itemID . "_*");

    //Check if cached images are resized versions of original file with format like img_84_250_320_h_Rm90byBQZXJmaWwuanBn.jpg
    for ($i=count($nombres_archivo)-1;$i>=0;$i--) {
        if (preg_match("/img_\d+_.*?_.*?_/i",$nombres_archivo[$i])) unset($nombres_archivo[$i]);
    }
    $nombres_archivo = array_values($nombres_archivo);

    if ($enable_image_cache && count($nombres_archivo) > 0) {
        // The  base image exists in cache
        $nombre_archivo = $nombres_archivo[0];
        $parts = explode(".", basename($nombre_archivo));
        $nombreSinExtension = $parts[0];
        $extension = $parts[1];
        $nombreSinExtension = explode("_", $nombreSinExtension);
        // Original file name is in the string after the last "_" so decode it
        $image_name = base64_decode(end($nombreSinExtension));
        $imageOriginal = file_get_contents($nombre_archivo);

    } else {
        $image          = getImage($clientID, $propertyID, $itemID);
        $imageOriginal = $image["RS_DATA"];
        $image_name     = $image["RS_NAME"];
        $extension      = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        // If image data is empty but the size field is > 0 then the image is in media server
        if ($image["RS_SIZE"] > 0 && $imageOriginal == '') {
            $fileData = getMediaFile($clientID,$itemID,$propertyID);
            $imageOriginal = $fileData['RS_DATA'];
        }

        // Save in cache base image
        if ($enable_image_cache) saveFileCache($imageOriginal, $directory . "img_" . $itemID, $image_name, $extension);
    }

    if ($imageOriginal == '') {
        dieWithError(404);
    }

    if ($extension !== "jpg" && $extension !== "jpeg" && $extension !== "gif" && $extension !== "png" && $extension !== "svg") {
        RSError("api_getPicture: Unknown extension: " . $extension);
        dieWithError(400);
    }

    if ($extension == "svg") {
        Header("Content-type: image/svg+xml");
        $svg_data = resizeSvg(urldecode($imageOriginal), $w, $h);
        saveImgCache($svg_data, $image_string, $image_name, $extension);
        echo $svg_data;

    } else {
        // Get width and height of the stored image
        $ow = imagesx(imagecreatefromstring($imageOriginal));
        $oh = imagesy(imagecreatefromstring($imageOriginal));

        //calculate new dimensions
        if ($w != ''){
            //passed dimension = force new dimension
            $nw = $w;
        }elseif ($h == ''){
            //no passed dimensions = original size
            $nw = $ow;
        }else{
            //passed only the other dimension = calculate this dimension
            if (($adj == 's') || ($adj == 'h')){
                $nw = (int)($ow * ($h / $oh));
            }else{
                $nw = $ow;
            }
        }
        if ($h != ''){
            //passed dimension = force new dimension
            $nh = $h;
        }elseif ($w == ''){
            //no passed dimensions = original size
            $nh = $oh;
        }else{
            //passed only the other dimension = calculate this dimension
            if (($adj == 's') || ($adj == 'w')){
                $nh = (int)($oh * ($w / $ow));
            }else{
                $nh = $oh;
            }
        }

        //avoid processing if no needed
        if (($nw != $ow) || ($nh != $oh)) {

            //calculate scaled size and displacement (before cropping the excess)
            $xscale = $nw / $ow;
            $yscale = $nh / $oh;

            if ((($xscale < $yscale) && ($adj == 's')) || (($xscale > $yscale) && ($adj == 'f')) || ($adj == 'w')) {
                $dw = $nw;
                $dh = (int)($oh * $xscale);
                $destX = 0;
                $destY = (int)(($nh - $dh) / 2);
            } elseif ((($xscale > $yscale) && ($adj == 's')) || (($xscale < $yscale) && ($adj == 'f')) || ($adj == 'h')) {
                $dw = (int)($ow * $yscale);
                $dh = $nh;
                $destX = (int)(($nw - $dw) / 2);
                $destY = 0;
            } elseif ($adj == 'c') {
                $dw = $ow;
                $dh = $oh;
                $destX = (int)(($nw - $dw) / 2);
                $destY = (int)(($nh - $dh) / 2);
            } else {
                //adj = 'd' || ($xscale == $yscale)
                $dw = $nw;
                $dh = $nh;
                $destX = 0;
                $destY = 0;
            }

            //security check (avoid less than 1px images)
            $nw = ($nw < 1) ? 1 : $nw;
            $nh = ($nh < 1) ? 1 : $nh;
            $dw = ($dw < 1) ? 1 : $dw;
            $dh = ($dh < 1) ? 1 : $dh;

            $image_thumb = imagecreatetruecolor($nw, $nh);

            if ($extension == 'gif') {
                $color_index = imagecolortransparent($imageOriginal);

                if ($color_index >= 0) {

                    $image_thumb = imagecreate($nw, $nh);
                    imagealphablending($image_thumb, true);

                    $rgb = imagecolorsforindex($imageOriginal, $color_index);
                    $background = imagecolorallocate($image_thumb, $rgb["red"], $rgb["green"], $rgb["blue"]);

                    imagefilledrectangle($image_thumb, 0, 0, $nw, $nh, $background);
                    imagecolortransparent($image_thumb, $background);
                }
            } elseif ($extension == 'png') {
                imagealphablending($image_thumb, false);
                imagesavealpha($image_thumb, true);
            } elseif ($extension == 'jpeg' || $extension == 'jpg') {
                $background = imagecolorallocate($image_thumb, 255, 255, 255);
                imagefilledrectangle($image_thumb, 0, 0, $nw, $nh, $background);
            }

            //create final image
            imagecopyresampled($image_thumb, imagecreatefromstring($imageOriginal), $destX, $destY, 0, 0, $dw, $dh, $ow, $oh);

            //and return the image
            switch($extension) {
                case "jpeg" :
                    Header("Content-type: image/jpeg");
                    echo imagejpeg($image_thumb, NULL, 90);
                    if ($enable_image_cache) saveImgCache($image_thumb, $image_string, $image_name, "jpeg");
                    break;

                case "jpg" :
                    Header("Content-type: image/jpeg");
                    echo imagejpeg($image_thumb, NULL, 90);
                    if ($enable_image_cache) saveImgCache($image_thumb, $image_string, $image_name, "jpg");
                    break;

                case "gif" :
                    Header("Content-type: image/gif");
                    echo imagegif($image_thumb);
                    if ($enable_image_cache) saveImgCache($image_thumb, $image_string, $image_name, "gif");
                    break;

                case "png" :
                    Header("Content-type: image/png");
                    echo imagepng($image_thumb);
                    if ($enable_image_cache) saveImgCache($image_thumb, $image_string, $image_name, "png");
                    break;
            }

        } else {
            // Return the original image
            Header("Content-type: image/" . $extension);
            if ($enable_image_cache) saveImgCache(imagecreatefromstring($imageOriginal), $image_string, $image_name, $extension);
            echo $imageOriginal;
        }
    }
}

/**
 * Resize Svg image
 */
function resizeSvg($svg_data, $w, $h) {
    // No hay resize, se devuelve tal cual
    if ($w == "" && $h == "") {
        return $svg_data;
    }

    // w y h definidos
    if ($w != "" && $h != "") {
        $dom = new DOMDocument;
        $dom -> loadXML($svg_data);
        foreach ($dom->getElementsByTagName('svg') as $item) {
            $item -> setAttribute('width', $w . 'px');
            $item -> setAttribute('height', $h . 'px');
        }
        return $dom -> saveXML();
    }
    // Si solo hay width, calculamoes el height por regla de 3
    if ($w != "") {
        $dom = new DOMDocument;
        $dom -> loadXML($svg_data);

        foreach ($dom->getElementsByTagName('svg') as $item) {
            $widthActual = $item -> getAttribute('width');
            $heightActual = $item -> getAttribute('height');
            $newHeight = ($w * $heightActual) / $widthActual;
        }

        foreach ($dom->getElementsByTagName('svg') as $item) {
            $item -> setAttribute('width', $w . 'px');
            $item -> setAttribute('height', $newHeight . 'px');
        }
        return $dom -> saveXML();
    }
    // Si solo hay height, calculamoes el width por regla de 3
    if ($h != "") {
        $dom = new DOMDocument;
        $dom -> loadXML($svg_data);

        foreach ($dom->getElementsByTagName('svg') as $item) {
            $widthActual = $item -> getAttribute('width');
            $heightActual = $item -> getAttribute('height');
            $newWidth = ($h * $widthActual) / $heightActual;
        }

        foreach ($dom->getElementsByTagName('svg') as $item) {
            $item -> setAttribute('width', $newWidth . 'px');
            $item -> setAttribute('height', $h . 'px');
        }
        return $dom -> saveXML();
    }
}

/**
 * Save Image in cache directory
 */
function saveImgCache($imageOriginal, $imagePath, $image_name, $extension) {
    global $directory;

    // Check if directory exists
    if (!file_exists($directory)) {
        if(!mkdir($directory, 0775, true)){
            RSError("api_getPicture: Could not create directory");
        }
    }

    switch($extension) {
        case "jpg" :
            return imagejpeg($imageOriginal, $imagePath . "_" . base64_encode($image_name) . "." . $extension);
        case "gif" :
            return imagegif($imageOriginal, $imagePath . "_" . base64_encode($image_name) . "." . $extension);
        case "png" :
            imagealphablending($imageOriginal, false);
            imagesavealpha($imageOriginal, true);
            return imagepng($imageOriginal, $imagePath . "_" . base64_encode($image_name) . "." . $extension);
        case "svg" :
            $file = $imagePath . "_" . base64_encode($image_name) . "." . $extension;
            $fh = fopen($file, "w");
            fwrite($fh, $imageOriginal);
            fclose($fh);
            return 0;
        default :
            return imagejpeg($imageOriginal, $imagePath . "_" . base64_encode($image_name) . "." . $extension);
    }
}
?>
