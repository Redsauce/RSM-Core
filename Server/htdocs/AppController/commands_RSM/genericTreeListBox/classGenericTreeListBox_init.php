<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID              = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['baseItemTypes']) ? $baseItemTypesNames    = $GLOBALS['RS_POST']['baseItemTypes'] : dieWithError(400);
isset($GLOBALS['RS_POST']['allowedItemTypes']) ? $allowedItemTypesNames = $GLOBALS['RS_POST']['allowedItemTypes'] : dieWithError(400);

//allowed itemTypes IDs & names
$itemTypesString  = '';
$allowedItemTypes = array();
$descendantsArray = array();
$iconsArray       = array();

if ($allowedItemTypesNames != '') {
  $allowedItemTypesArr = explode(",", $allowedItemTypesNames);

  foreach ($allowedItemTypesArr as $allowedItemType) {
    //check if itemtypeID or referred appItemTypeName is passed
    $itemTypeID = ParseITID($allowedItemType, $clientID);
    $allowedItemTypes[] = $itemTypeID;
    $itemTypesString .= $itemTypeID . "," . base64_encode(getClientItemTypeName($itemTypeID, $clientID)) . ";";
  }

  foreach ($allowedItemTypes as $allowedItemType) {
    $subDescendants = getDescendantsLevel($clientID, $allowedItemType, $allowedItemTypes);

    $descendants_string = '';
    foreach ($subDescendants as $subDescendant) {
      $descendants_string .= $subDescendant['propertyID'] . "," . $subDescendant['itemTypeID'] . "," . $subDescendant['propertyType'] . "," . base64_encode($subDescendant['propertyName']) . ";";
    }

    $descendants_string = rtrim($descendants_string, ";");

    if (!array_key_exists($allowedItemType, $descendantsArray)) {
      $descendantsArray[$allowedItemType] = $descendants_string;
    }

    //get Icon
    if (!array_key_exists($allowedItemType, $iconsArray)) {
      $iconsArray[$allowedItemType] = getClientItemTypeIcon($allowedItemType, $clientID);
    }
  }
} else {
  // there are not specified allowed itemtypes, so get all
  $theQuery = "SELECT `RS_ITEMTYPE_ID`, `RS_NAME`, `RS_ICON` FROM `rs_item_types` WHERE `RS_CLIENT_ID`='" . $clientID . "' ORDER BY `RS_ORDER`";

  // Query the database
  $res = RSquery($theQuery);

  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $itemTypesString .= $row['RS_ITEMTYPE_ID'] . "," . base64_encode($row['RS_NAME']) . ";";

      $subDescendants = getDescendantsLevel($clientID, $row['RS_ITEMTYPE_ID'], $allowedItemTypes);

      $descendants_string = '';
      foreach ($subDescendants as $subDescendant) {
        $descendants_string .= $subDescendant['propertyID'] . "," . $subDescendant['itemTypeID'] . "," . $subDescendant['propertyType'] . "," . base64_encode($subDescendant['propertyName']) . ";";
      }

      $descendants_string = rtrim($descendants_string, ";");

      if (!array_key_exists($row['RS_ITEMTYPE_ID'], $descendantsArray)) {
        $descendantsArray[$row['RS_ITEMTYPE_ID']] = $descendants_string;
      }

      //get Icon
      if (!array_key_exists($allowedItemType, $iconsArray)) {
        $iconsArray[$row['RS_ITEMTYPE_ID']] = bin2hex($row['RS_ICON']);
      }
    }
  }
}

$descendantsString = '';
foreach ($descendantsArray as $availableItemType => $descendantsForItem) {
  $descendantsString .= $availableItemType . "=>" . $descendantsForItem . ":";
}

$iconsString = '';
foreach ($iconsArray as $availableItemType => $iconForItem) {
  $iconsString .= $availableItemType . "=>" . $iconForItem . ":";
}


$results['iconsForItemType'] = rtrim($iconsString, ":");
$results['descendantsForItemType'] = rtrim($descendantsString, ":");
$results['itemTypeNames'] = rtrim($itemTypesString, ";");
$results['allowedItemTypeIDs'] = implode(",", $allowedItemTypes);

// base itemTypes
$baseItemTypesArr = explode(",", $baseItemTypesNames);

$results['baseItemTypeIDs'] = '';

foreach ($baseItemTypesArr as $baseItemType) {
  // Check if itemtypeID or referred appItemTypeName is passed
  $results['baseItemTypeIDs'] .= ParseITID($baseItemType, $clientID) . ",";
}

$results['baseItemTypeIDs'] = rtrim($results['baseItemTypeIDs'], ",");

// And write XML Response back to the application
RSreturnArrayResults($results);
