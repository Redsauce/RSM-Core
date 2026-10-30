<?php
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMbinaryUploads.php';

$saved = false;
try {
    $name = base64_decode($GLOBALS[$cstRS_POST][$cstName], true);
    $icon = $GLOBALS[$cstRS_POST][$cstItemtypeIcon];
    $saved = RSupdateItemTypeWithIcon($GLOBALS[$cstRS_POST][$cstClientID], $GLOBALS[$cstRS_POST][$cstItemtypeID], $name, $icon);
} catch (Throwable $exception) {
    $saved = false;
}
if (!$saved) RSReturnError('ERROR WHILE UPDATING ITEMTYPE', '15');
RSReturnArrayResults(array('result' => 'OK', 'name' => $name, 'itemtypeIcon' => $icon));
