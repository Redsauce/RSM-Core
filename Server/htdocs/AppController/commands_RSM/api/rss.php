<?php
require_once "../utilities/RSMfeeds.php";

// Clean GET data in order to avoid SQL injections
$search  = array("'", "\"");
$replace = array("" , ""  );

foreach ($_GET as $key => $value) $GLOBALS["RS_GET"][$key] = str_replace($search, $replace, $value);

$token = isset($GLOBALS["RS_GET"]["RStoken"])? $GLOBALS["RS_GET"]["RStoken"] : dieWithError(400); // No token, no way
$tema  = isset($GLOBALS["RS_GET"]["feed"   ])? $GLOBALS["RS_GET"]["feed"   ] :    "";
$pIDs  = isset($GLOBALS["RS_GET"]["pIDs"   ])? $GLOBALS["RS_GET"]["pIDs"   ] :    "";
$tema  = isset($GLOBALS["RS_GET"]["title"  ])? $GLOBALS["RS_GET"]["title"  ] :    $tema;

$filterJoining  = "AND";
$filterRules    = "";

if ($pIDs == "") {
    // No propertyIDs have been defined, so use the defaul ones
    $pIDs = "news.title,news.description,news.image,news.author,news.date,news.URL"; // Default
    $extFilterRules = "newsType.title;" . base64_encode($tema) . ";=";
} else {
    // As there are defined propertyIDs, we won't filter using the parent
    // We use";" as separator since the "," character seems to break simplePIE
    $pIDs = explode(";", $pIDs);
    $pIDs = implode(",", $pIDs);
    $extFilterRules = "";
}
header("Content-Type: application/xml; charset=UTF-8");
echo getRSS($tema, $token, $pIDs, $filterRules, $extFilterRules, $filterJoining);
?>
