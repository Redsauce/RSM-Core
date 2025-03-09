<?php
//****************************************//
//getScaledPicture.php
//
//Description:
//    returns a picture with selected size
//
// Possible params:
//        ID: integer: id of the item containing the image to retrieve
//        propertyID: integer: id of the property of the item that contains the picture
//             w: integer: selected width (same as original by default)
//             h: integer: selected height (same as original by default)
//             adj:  string: adjust scale to selected dimension (s by default):
//                      (s(how all: the image is scaled proportionally to fit completely inside passed dimensions leaving blank space if needed)|
//                       f(ill all: the image is scaled proportionally to fit lesser dimmension, centered and cropped to fit the other dimension)|
//                       w(idth: the image is centered and scaled proportionally to fit the passed width and cropped in height if needed)|
//                       h(eight: the image is centered and scaled proportionally to fit the passed height and cropped in width if needed)|
//                       d(eform: the image is scaled unproportionally to fit completely both passed dimensions)|
//                       c(rop: the image is centered and cropped if it's bigger than passed dimensions without scaling))
//returns:
//    string: picture binary stream
//
//
//  EXAMPLE:
//  route/get.php?propertyID=886&ID=87
//
//****************************************//
require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";

setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../../utilities/RSMtokensManagement.php";
require_once "../../../utilities/RSMcacheManagement.php";
require_once "../../api_headers.php";


$parameters = getRequestParams();
validateRequestParams($parameters);

$RStoken =  getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$propertyID = $parameters["propertyID"];
$ID = $parameters["ID"];
$propertyID = $parameters["propertyID"];

array_key_exists("w", $parameters) ? $w = $parameters["w"] : $w = "";
array_key_exists("h", $parameters) ? $h = $parameters["h"] : $h = "";
array_key_exists("adj", $parameters) ? $adj = $parameters["adj"] : $adj = "";

// Check token permissions
if (!RShasREADTokenPermission($RStoken, $propertyID)) {
    $RSallowDebug ? returnJsonMessage(403, "Token has no permissions to get this picture" ) : returnJsonMessage(403, "");
}
// Check if asked property is image
if (getPropertyType($propertyID, $clientID) != 'image') {
    $RSallowDebug ? returnJsonMessage(404, "Property is not an image" ) : returnJsonMessage(404, "");
}
// Check if item exists
$itemTypeID = getItemTypeIDFromProperties([$propertyID], $clientID);
if (!verifyItemExists($ID, $itemTypeID, $clientID)) {
    $RSallowDebug ? returnJsonMessage(404, "Item doesn't exist" ) : returnJsonMessage(404, "");
}

$directory = $RSimageCache . "/" . $clientID . "/" . $propertyID . "/";
$image_name = "img_" . $ID . "_" . $w . "_" . $h . "_" . $adj;
$image_string = $directory . $image_name;

//check image in cache
$nombres_archivo = glob($image_string . "_*");

if ($enable_image_cache && !empty($nombres_archivo)) {

    // The image exists in cache
    $nombre_archivo = $nombres_archivo[0];
    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);
    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(rawurldecode(end($nombreSinExtension)));

    // The file was found in the cache. Return the cached file
    header('Content-type: ' . mime_content_type($nombre_archivo));
    header('Content-Disposition: inline; filename="' . $nombre_descarga . '"');

    readfile($nombre_archivo);
} else {
    //check base image in cache
    $nombres_archivo = glob($directory . "img_" . $ID . "_*");

    //Check if cached images are resized versions of original file with format like img_84_250_320_h_Rm90byBQZXJmaWwuanBn.jpg
    for ($i = count($nombres_archivo) - 1; $i >= 0; $i--) {
        if (preg_match("/img_\d+_\d*_\d*_/i", $nombres_archivo[$i])) {
            unset($nombres_archivo[$i]);
        }
    }
    $nombres_archivo = array_values($nombres_archivo);

    if ($enable_image_cache && !empty($nombres_archivo)) {
        // The  base image exists in cache
        $nombre_archivo = $nombres_archivo[0];
        $parts = explode(".", basename($nombre_archivo));
        $nombreSinExtension = $parts[0];
        $extension = $parts[1];
        $nombreSinExtension = explode("_", $nombreSinExtension);
        // Original file name is in the string after the last "_" so decode it
        $image_name = base64_decode(rawurldecode(end($nombreSinExtension)));
        $imageOriginal = file_get_contents($nombre_archivo);
    } else {
        $image          = getImage($clientID, $propertyID, $ID);
        $imageOriginal = $image["RS_DATA"];
        $image_name     = $image["RS_NAME"];
        $extension      = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        // If image data is empty but the size field is > 0 then the image is in media server
        if ($image["RS_SIZE"] > 0 && $imageOriginal == '') {
            $fileData = getMediaFile($clientID, $ID, $propertyID);
            $imageOriginal = $fileData['RS_DATA'];
        }

        // Save in cache base image
        if ($enable_image_cache && $imageOriginal != '') {
            saveFileCache($imageOriginal, $directory . "img_" . $ID, $image_name, $extension);
        }
    }

    if ($imageOriginal == '') {
        $RSallowDebug ? returnJsonMessage(404, "No image found" ) : returnJsonMessage(404, "");
    }

    if ($extension !== "jpg" && $extension !== "jpeg" && $extension !== "gif" && $extension !== "png" && $extension !== "svg") {
        RSError("api_getPicture: Unknown extension: " . $extension);

        $RSallowDebug ? returnJsonMessage(400, "Unknown extension" ) : returnJsonMessage(400, "");
    }

    if ($extension == "svg") {
        Header("Content-type: image/svg+xml");

        if ($w == "" && $h == "") {
            // No hay resize, se queda como está
            $svg_data = urldecode($imageOriginal);
        } else {
            $svg_data = resizeSvg(urldecode($imageOriginal), $w, $h, $adj);
        }

        saveImgCache($svg_data, $image_string, $image_name, $extension);
        echo $svg_data;
    } else {
        // Get width and height of the stored image
        $originalImage = imagecreatefromstring($imageOriginal);

        if ($originalImage === false) {
            // The original image is not valid
            RSError("api_getPicture: not a valid image: " . $imageOriginal);
            $RSallowDebug ? returnJsonMessage(400, "Not a valid image" ) : returnJsonMessage(400, "");
        } else {
            // Valid image, continue processing it
            $ow = imagesx($originalImage);
            $oh = imagesy($originalImage);

            //calculate new dimensions
            if ($w != '') {
                //passed dimension = force new dimension
                $nw = $w;
            } elseif ($h == '') {
                //no passed dimensions = original size
                $nw = $ow;
            } else {
                //passed only the other dimension = calculate this dimension
                if (($adj == 's') || ($adj == 'h')) {
                    $nw = (int)($ow * ($h / $oh));
                } else {
                    $nw = $ow;
                }
            }
            if ($h != '') {
                //passed dimension = force new dimension
                $nh = $h;
            } elseif ($w == '') {
                //no passed dimensions = original size
                $nh = $oh;
            } else {
                //passed only the other dimension = calculate this dimension
                if (($adj == 's') || ($adj == 'w')) {
                    $nh = (int)($oh * ($w / $ow));
                } else {
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
                imagecopyresampled($image_thumb, $originalImage, $destX, $destY, 0, 0, $dw, $dh, $ow, $oh);

                //and return the image
                switch ($extension) {
                    case "jpeg":
                        Header("Content-type: image/jpeg");
                        echo imagejpeg($image_thumb, null, 90);
                        if ($enable_image_cache) {
                            saveImgCache($image_thumb, $image_string, $image_name, "jpeg");
                        }
                        break;

                    case "jpg":
                        Header("Content-type: image/jpeg");
                        echo imagejpeg($image_thumb, null, 90);
                        if ($enable_image_cache) {
                            saveImgCache($image_thumb, $image_string, $image_name, "jpg");
                        }
                        break;

                    case "gif":
                        Header("Content-type: image/gif");
                        echo imagegif($image_thumb);
                        if ($enable_image_cache) {
                            saveImgCache($image_thumb, $image_string, $image_name, "gif");
                        }
                        break;

                    case "png":
                        Header("Content-type: image/png");
                        echo imagepng($image_thumb);
                        if ($enable_image_cache) {
                            saveImgCache($image_thumb, $image_string, $image_name, "png");
                        }
                        break;

                    default:
                    returnJsonMessage(400, "");
                    break;
                }
            } else {
                // Return the original image
                Header("Content-type: image/" . $extension);
                echo $imageOriginal;
            }
        }
    }
}


/**
 * Resize Svg image
 */
function resizeSvg($svgdata, $w, $h, $adj)
{
    $dom = new DOMDocument;
    $dom->loadXML($svgdata);

    foreach ($dom->getElementsByTagName('svg') as $item) {
        // Get current sizing info in svg
        $widthActual = $item->getAttribute('width');
        $heightActual = $item->getAttribute('height');
        $viewBoxActual = $item->getAttribute('viewBox');
        $aspectRatioActual = $item->getAttribute('preserveAspectRatio');

        //set default aspect ratio if not defined

        $aspectRatioActual = ($aspectRatioActual == "") ?  "xMidYMid meet" : $aspectRatioActual;

        //get original ratio positioning, if it is none, set default value
        $aspectRatioParts = explode(' ', $aspectRatioActual);
        $aspectRatioParts = (count($aspectRatioParts) < 2) ? array("xMidYMid", "meet") : $aspectRatioParts;

        if ($viewBoxActual == "") {
            // The svg has no viewBox so can't be scaled unless we create one
            if ($widthActual != "" && $heightActual != "") {
                // Create the viewBox
                $item->setAttribute('viewBox', "0 0 " . $widthActual . " " . $heightActual);
            } else {
                // Not enough info to create a viewBox so return original image
                return $svgdata;
            }
        } else {
            $viewBoxParts = explode(' ', $viewBoxActual);
            $widthActual = $widthActual == "" ? $viewBoxParts[2] : $widthActual;
            $heightActual = $heightActual == "" ? $viewBoxParts[3] : $heightActual;
        }

        if ($adj == "s") {
            //show all the image inside passed dimensions
            if ($w != "" && $h != "") {
                // w y h definidos
                $newWidth = $w;
                $newHeight = $h;
            } elseif ($w != "") {
                // Si solo hay width, calculamos el height por regla de 3
                $newWidth = $w;
                $newHeight = ($w * $heightActual) / $widthActual;
            } else {
                // Si solo hay height, calculamos el width por regla de 3
                $newWidth = ($h * $widthActual) / $heightActual;
                $newHeight = $h;
            }
        } elseif ($adj == "f") {
            //fill (at least) the passed dimensions with the image
            if ($w != "" && $h != "") {
                // w y h definidos
                $scaleWidth = $w / $widthActual;
                $scaleHeight = $h / $heightActual;
                if ($scaleWidth > $scaleHeight) {
                    $newWidth = $w;
                    $newHeight = $heightActual * $scaleWidth;
                } else {
                    $newWidth = $widthActual * $scaleHeight;
                    $newHeight = $h;
                }
            } elseif ($w != "") {
                // Si solo hay width, calculamos el height por regla de 3
                $newWidth = $w;
                $newHeight = ($w * $heightActual) / $widthActual;
            } else {
                // Si solo hay height, calculamos el width por regla de 3
                $newWidth = ($h * $widthActual) / $heightActual;
                $newHeight = $h;
            }
        } elseif ($adj == "w") {
            //scale the image to passed width (can ignore passed height)
            if ($w != "") {
                // Calculamos el height por regla de 3
                $newWidth = $w;
                $newHeight = ($w * $heightActual) / $widthActual;
            } else {
                // Si no hay width no podemos escalar asi que devolvemos el original
                return $svgdata;
            }
        } elseif ($adj == "h") {
            //scale the image to passed height (can ignore passed width)
            if ($h != "") {
                // Calculamos el width por regla de 3
                $newWidth = ($h * $widthActual) / $heightActual;
                $newHeight = $h;
            } else {
                // Si no hay height no podemos escalar asi que devolvemos el original
                return $svgdata;
            }
        } elseif ($adj == "d") {
            //deform the image
            $item->setAttribute('preserveAspectRatio', "none");
            if ($w != "" && $h != "") {
                // w y h definidos
                $newWidth = $w;
                $newHeight = $h;
            } elseif ($w != "") {
                // Si solo hay width, el height es el original
                $newWidth = $w;
                $newHeight = $heightActual;
            } else {
                // Si solo hay height, el width es el original
                $newWidth = $widthActual;
                $newHeight = $h;
            }
        } elseif ($adj == "c") {
            //crop means we remove the excess of the image
            $item->setAttribute('preserveAspectRatio', $aspectRatioParts[0] . " slice");
            if ($w != "" && $h != "") {
                // w y h definidos
                $newWidth = $w;
                $newHeight = $h;
            } elseif ($w != "") {
                // Si solo hay width, el height es el original
                $newWidth = $w;
                $newHeight = $heightActual;
            } else {
                // Si solo hay height, el width es el original
                $newWidth = $widthActual;
                $newHeight = $h;
            }
        }

        $item->setAttribute('width', $newWidth . 'px');
        $item->setAttribute('height', $newHeight . 'px');
    }
    return $dom->saveXML();
}

/**
 * Save Image in cache directory
 */
function saveImgCache($imageOriginal, $imagePath, $imagename, $extension)
{
    global $directory;
    $result = null;

    // Check if directory exists
    if (!file_exists($directory) && !mkdir($directory, 0775, true)) {
        RSError("api_getPicture: Could not create directory");
    }

    switch ($extension) {
        case "jpg":
            $result = imagejpeg($imageOriginal, $imagePath . "_" . rawurlencode(base64_encode($imagename)) . "." . $extension);
            break;
        case "gif":
            $result = imagegif($imageOriginal, $imagePath . "_" . rawurlencode(base64_encode($imagename)) . "." . $extension);
            break;
        case "png":
            imagealphablending($imageOriginal, false);
            imagesavealpha($imageOriginal, true);
            $result = imagepng($imageOriginal, $imagePath . "_" . rawurlencode(base64_encode($imagename)) . "." . $extension);
            break;
        case "svg":
            $file = $imagePath . "_" . rawurlencode(base64_encode($imagename)) . "." . $extension;
            $fh = fopen($file, "w");
            fwrite($fh, $imageOriginal);
            fclose($fh);
            $result = 0;
            break;
        default:
            $result = imagejpeg($imageOriginal, $imagePath . "_" . rawurlencode(base64_encode($imagename)) . "." . $extension);
            break;
    }
    return $result;
}

function validateRequestParams($parameters)
{
    checkParamsContains($parameters, "ID");
    checkParamsContains($parameters, "propertyID");
    checkStringIsInteger($parameters["w"]);
    checkStringIsInteger($parameters["h"]);
    checkADJParamIsValid($parameters);
}
