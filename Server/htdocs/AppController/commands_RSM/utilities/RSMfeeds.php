<?php
require_once "RSconfiguration.php";

/*
 * Get Feed Info (newsType)
 */
function getNewsType($token, $tema) {
    global $RSMapiURL;
    $ch = curl_init($RSMapiURL . "api_getItems.php");
    $propertiesId = "newsType.description,newsType.language,newsType.URL";
    $filterRules = "";
    $extFilterRules = "newsType.title;" . base64_encode($tema) . ";=";
    $filterJoining = "AND";
    $data = "RStoken=" . $token . "&propertyIDs=" . $propertiesId . "&filterRules=" . $filterRules . "&extFilterRules=" . $extFilterRules . "&filterJoining=" . $filterJoining;

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $xmlret = curl_exec($ch);

    $xml = simplexml_load_string($xmlret);

    foreach ($xml->rows->row as $row) {
        return $row -> column;
    }

}

function getRSS($RSStitle, $token, $propertyIDs, $filterRules, $extFilterRules, $filterJoining) {
    global $RSMapiURL;

    $newsType = getNewsType($token, $RSStitle);

    if ($newsType[0] != "NOK") {
        $RSSdescription = $newsType[1];
        $RSSlanguage    = $newsType[2];
        $RSSlink        = $newsType[3];
    } else {
        $RSSdescription = "";
        $RSSlanguage = "";
        $RSSlink = "";
    }

    // Get News	 Info
    $ch = curl_init($RSMapiURL . "api_getItems.php");
    $data = "RStoken=" . $token . "&propertyIDs=" . $propertyIDs . "&filterRules=" . $filterRules . "&extFilterRules=" . $extFilterRules . "&filterJoining=" . $filterJoining;

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $xmlret = curl_exec($ch);

    curl_close($ch);

    header('Content-type: application/xml');

    // parse News xml
    $xml = simplexml_load_string($xmlret);

    // Create RSS Document
    $pDom = new DOMDocument();
    $pRSS = $pDom -> createElement('rss');
    $pRSS -> setAttribute('version', "2.0");
    $pDom -> appendChild($pRSS);
    $pChannel = $pDom -> createElement('channel');
    $pRSS -> appendChild($pChannel);

    $pChannel -> appendChild($pDom -> createElement('title', $RSStitle));
    $pChannel -> appendChild($pDom -> createElement('link', $RSSlink));
    $pChannel -> appendChild($pDom -> createElement('description', $RSSdescription));
    $pChannel -> appendChild($pDom -> createElement('language', $RSSlanguage));

    foreach ($xml->rows->row as $row) {
        $pItem = $pDom -> createElement('item');
        $pChannel -> appendChild($pItem);

        $newsId      = htmlspecialchars($row -> column[0]);
        $titulo      = htmlspecialchars($row -> column[1]);
        $descripcion = htmlspecialchars($row -> column[2]);
        $imagenName  = htmlspecialchars($row -> column[3]);
        $autor       = htmlspecialchars($row -> column[4]);
        $fecha       = htmlspecialchars($row -> column[5]);
        $link        = htmlspecialchars($row -> column[6]);

        if ($titulo != "") {
            $pTitle = $pDom -> createElement('title', $titulo);
            $pItem -> appendChild($pTitle);
        }

        if ($descripcion != "") {
            $pDesc = $pDom -> createElement('description', $descripcion);
            $pItem -> appendChild($pDesc);
        }

        if ($link != "") {
            $pLink = $pDom -> createElement('link', $link);
            $pItem -> appendChild($pLink);
        }

        if ($autor != "") {
            $pAuthor = $pDom -> createElement('author', $autor);
            $pItem -> appendChild($pAuthor);
        }

        if ($fecha != "") {
            $pDate = $pDom -> createElement('pubDate', $fecha);
            $pItem -> appendChild($pDate);
        }

        if ($imagenName != "" && $imagenName != ":0") {
            $pImg = $pDom -> createElement('image');
            $pItem -> appendChild($pImg);

            $urlGetPicture = $RSMapiURL . "api_getPicture.php";
            $imageUrl = $urlGetPicture . "?itemID=" . $newsId . "&amp;propertyID=606&amp;RStoken=" . $token;
            $pImgUrl = $pDom -> createElement('url', $imageUrl);
            $pImg -> appendChild($pImgUrl);

            $pImgTitle = $pDom -> createElement('title', $imagenName);
            $pImg -> appendChild($pImgTitle);
        }
    }

    return $pDom -> saveXML();
}
?>
