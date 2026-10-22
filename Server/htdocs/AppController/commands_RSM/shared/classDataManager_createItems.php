<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
$clientID   =              $GLOBALS[$cstRS_POST]['clientID'  ];
$items      = explode(":", $GLOBALS[$cstRS_POST]['properties']);

$itemIDs = array();

foreach ($items as $item) {
    // prepare the propertiesValues array
    $propertiesValues = array();
    if ($item != '') {
        $properties = explode(',', $item);
        foreach ($properties as $property) {
            $propertiesArr = explode(';', $property);

            // add to the propertiesValues array
            $propertiesValues[] = array('ID' => $propertiesArr[0], 'value' => base64_decode($propertiesArr[1]));
        }
    }

    // Create the item
    $newID = createItem($clientID, $propertiesValues);
    if ($newID <= 0) {
        RSReturnArrayResults(array('result' => 'NOK', 'description' => 'Unable to create item', 'itemIDs' => implode(',', $itemIDs)));
        exit();
    }
    $itemIDs[] = $newID;
}

$results['itemIDs'] = implode(",", $itemIDs);

// Return results
RSReturnArrayResults($results);
