<?php
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';
require_once '../utilities/RSMbinaryUploads.php';

$clientID = $GLOBALS[$cstRS_POST]['clientID'];
$itemTypeID = $GLOBALS[$cstRS_POST]['itemTypeID'];
$overwrite = $GLOBALS[$cstRS_POST]['overwrite'] == 1;
$trigger = $GLOBALS[$cstRS_POST]['allowTriggerEvents'] == 1;
try {
    $itemIDs = $GLOBALS[$cstRS_POST]['itemIDs'] === '' ? array() : explode(',', $GLOBALS[$cstRS_POST]['itemIDs']);
    $propertyIDs = $GLOBALS[$cstRS_POST]['propertyIDs'] === '' ? array() : explode(',', $GLOBALS[$cstRS_POST]['propertyIDs']);
    $rows = array_map(function ($row) { return explode(' ', $row); }, explode(':', $GLOBALS[$cstRS_POST]['propertiesList']));
    $results = RSimportItemsWithProperties($clientID, $itemTypeID, $itemIDs, $propertyIDs, $rows, $overwrite);
} catch (LengthException $exception) {
    $results = array('result' => 'ERROR0', 'description' => 'File/image exceeds the configured size limit');
} catch (Throwable $exception) {
    $results = array('result' => 'ERROR0', 'description' => 'Unable to import items');
}
if ($results['result'] === 'OK') {
    $itemIDs = $results['itemIDs'];
    unset($results['itemIDs']);
    if ($trigger) {
        $affectedItems = array_map(function ($itemID) use ($itemTypeID) { return intval($itemTypeID) . ',' . $itemID; }, $itemIDs);
        $RSMsplitTriggers = true;
        if ($overwrite) $RSMupdatedItemIDs = $affectedItems;
        else $RSMcreatedItemIDs = $affectedItems;
    }
}
RSReturnArrayResults($results);
