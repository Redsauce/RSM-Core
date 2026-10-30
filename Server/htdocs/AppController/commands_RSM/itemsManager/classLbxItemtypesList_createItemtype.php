<?php
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMidentificationFunctions.php';
require_once '../utilities/RSMbinaryUploads.php';

$results = array('result' => 'NOK');
try {
    $name = base64_decode($GLOBALS[$cstRS_POST]['itemtypeName'], true);
    $icon = $GLOBALS[$cstRS_POST]['itemtypeIcon'];
    $itemTypeID = RScreateItemTypeWithIcon($GLOBALS[$cstRS_POST][$cstClientID], $name, $icon);
    if ($itemTypeID !== false) {
        $results = array('result' => 'OK', 'itemtypeID' => $itemTypeID, 'itemtypeName' => $name, 'itemtypeIcon' => $icon);
    }
} catch (LengthException $exception) {
    $results['description'] = 'Image exceeds the configured size limit';
} catch (Throwable $exception) {
    $results['description'] = 'Unable to create item type';
}
RSReturnArrayResults($results);
