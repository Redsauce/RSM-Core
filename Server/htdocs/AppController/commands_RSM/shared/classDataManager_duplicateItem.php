<?php
//***************************************************
// Description:
//  DuplicateItem
//
// parameters:
// clientID   => the client ID
// itemType   => the item type ID
// itemID      => the item to duplicate
// numCopies  => the number of copies
// properties => the properties to set after duplicating (IDS and values)
//
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// Definitions
isset($GLOBALS['RS_POST']['clientID'   ]) ? $clientID     =              $GLOBALS['RS_POST']['clientID'   ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID' ]) ? $itemTypeID   =              $GLOBALS['RS_POST']['itemTypeID' ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['itemID'     ]) ? $itemID       =              $GLOBALS['RS_POST']['itemID'     ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['numCopies'  ]) ? $numCopies    =              $GLOBALS['RS_POST']['numCopies'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['properties' ]) ? $properties   =              $GLOBALS['RS_POST']['properties' ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['descendants']) ? $descendants  = explode(',', $GLOBALS['RS_POST']['descendants']) : $descendants = array();

// organize descendants by parent itemtype
$descendantsForItemtype = array();
for ($i = 0; $i < count($descendants); $i++) {
  $descendant = explode(';', $descendants[$i]);
  if (count($descendant) == 2) {
    $descendantParent = getClientPropertyReferredItemType($descendant[1], $clientID);
    if (!isset($descendantsForItemtype[$descendantParent])) {
      $descendantsForItemtype[$descendantParent] = array();
    }
    $descendantsForItemtype[$descendantParent][] = $descendant;
  }
}

// duplicate item
$newItemIDs = duplicateItem($itemTypeID, $itemID, $clientID, $numCopies, $descendantsForItemtype);



if (!is_array($newItemIDs)) {
  $newItemIDs = array($newItemIDs);
}

if ($properties != '') {
  $properties = explode(',', $properties);

  foreach ($properties as $property) {
    $propertyArr = explode(';', $property);

    // update properties requested
    foreach ($newItemIDs as $newItemID) {
      setPropertyValueByID($propertyArr[0], $itemTypeID, $newItemID, $clientID, base64_decode($propertyArr[1]), '', $RSuserID);
    }
  }
}


// Return the IDs of the new elements created
$elements = array();
if (count($newItemIDs) == 1) {
  array_push($elements, $newItemIDs[0]);
} else {
  foreach ($newItemIDs as $elementID) {
    array_push($elements, $elementID[array_keys($elementID)[0]]);
  }
}

$results['newItemIDs'] = implode(',', $elements);
RSReturnArrayResults($results);
