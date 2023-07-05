<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Definitions
$clientID         =             $GLOBALS['RS_POST']['clientID'];
$itemTypeID       =             $GLOBALS['RS_POST']['itemTypeID'];
$count            =             $GLOBALS['RS_POST']['count'];
$itemIDs          = explode(",", $GLOBALS['RS_POST']['itemIDs']);
$allowedItemTypes =             $GLOBALS['RS_POST']['allowedItemTypes'];

//Disable recursive multiplication until it's fixed
// TO DO: fix recursive itemtypes multiplication
isset($GLOBALS['RS_POST']['descendants']) ? $descendants  = explode(';', $GLOBALS['RS_POST']['descendants']) : $descendants = false;

if ($allowedItemTypes != '') {
  $allowedItemTypes = explode(",", $allowedItemTypes);
} else {
  $allowedItemTypes = array();
}

if ($clientID != 0 && $clientID != "") {
  if ($itemTypeID != 0 && $itemTypeID != "") {
    if (!empty($itemIDs) && $itemIDs[0] != "" && $itemIDs[0] != 0) {
      if ($count > 0) {
        // organize descendants by parent itemtype
        $descendantsForItemtype = array();

        $hasUnproccessedChilds = 0;
        if ($descendants === false) {
          // Chech if there are any descendant items and request for descendants selection in that case (continue otherwise)
          //get all descendants of this itemtype
          $subDescendants = getDescendantsLevel($clientID, $itemTypeID, $allowedItemTypes);

          foreach ($itemIDs as $itemID) {
            foreach ($subDescendants as $subDescendant) {
              // build filter
              $filterProperties = array();
              $filterProperties[] = array('ID' => $subDescendant['propertyID'], 'value' => $itemID);

              $returnProperties = array();

              // get items pertaining to the parent passed
              $subResult = getFilteredItemsIDs($subDescendant['itemTypeID'], $clientID, $filterProperties, $returnProperties);
              if (count($subResult) > 0) {
                $hasUnproccessedChilds = 1;
                break 2;
              }
            }
          }
        } else {
          for ($i = 0; $i < count($descendants); $i++) {
            $descendant = explode(',', $descendants[$i]);
            if (count($descendant) == 2) {
              //check recursive itemtype
              if ($descendant[1] == '0') {
                $descendantParent = $descendant[0];
                $descendant[1] = getRecursivePropertyID($descendant[0], $clientID);
              } else {
                $descendantParent = getClientPropertyReferredItemType($descendant[1], $clientID);
              }
              if (!isset($descendantsForItemtype[$descendantParent])) {
                $descendantsForItemtype[$descendantParent] = array();
              }
              $descendantsForItemtype[$descendantParent][] = $descendant;
            }
          }
        }

        if ($hasUnproccessedChilds == 0) {
          //begin transaction
          $mysqli->begin_transaction();
          $resultIDs = array();
          foreach ($itemIDs as $itemID) {
            //duplicate Item
            $newItemIDs = duplicateItem($itemTypeID, $itemID, $clientID, $count, $descendantsForItemtype);

            if (is_array($newItemIDs)) {
              $resultIDs = array_merge($resultIDs, $newItemIDs);
            } elseif ($newItemIDs > 0) {
              $resultIDs[] = $newItemIDs;
            } else {
              //rollback transaction
              $mysqli->rollback();
              $results['result'] = "NOK";
              $results['description'] = "ERROR CREATING ITEMS";
            }
          }

          //commit transaction
          $mysqli->commit();
          $results['itemIDs'] = implode(",", $resultIDs);
          $results['result'] = 'OK';
        } else {
          $results['result'] = 'NOK';
          $results['description'] = 'RECURSIVE COPY REQUIRED';
        }
      } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID NUMBER OF COPIES";
      }
    } else {
      $results['result'] = "NOK";
      $results['description'] = "INVALID ITEM";
    }
  } else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID ITEMTYPE";
  }
} else {
  $results['result'] = "NOK";
  $results['description'] = "INVALID CLIENT";
}

// Return results
RSReturnArrayResults($results);
