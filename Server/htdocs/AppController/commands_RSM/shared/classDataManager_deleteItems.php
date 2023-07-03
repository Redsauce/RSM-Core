<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";


// Definitions
isset($GLOBALS['RS_POST']['clientID'   ]) ? $clientID     =              $GLOBALS['RS_POST']['clientID'   ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID' ]) ? $itemTypeID   =              $GLOBALS['RS_POST']['itemTypeID' ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['items'      ]) ? $items        =              $GLOBALS['RS_POST']['items'      ]  : dieWithError(400);
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

if ($items != '' && count(getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID)) > 0) {
  // the user can delete the items
  if (strpos($items, ',') === false) {
    deleteItem($itemTypeID, $items, $clientID);
  } else {
    deleteItems($itemTypeID, $clientID, $items);
  }
}


$results['result'] = 'OK';

// Return results
RSReturnArrayResults($results);
