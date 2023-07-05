<?php
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Definitions
isset($GLOBALS['RS_POST']['clientID'        ]) ? $clientID   = $GLOBALS['RS_POST']['clientID'        ] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID'      ]) ? $itemTypeID = $GLOBALS['RS_POST']['itemTypeID'      ] : dieWithError(400);
isset($GLOBALS['RS_POST']['parentID'        ]) ? $parentID   = $GLOBALS['RS_POST']['parentID'        ] : dieWithError(400);
isset($GLOBALS['RS_POST']['parentPropertyID']) ? $parentPID  = $GLOBALS['RS_POST']['parentPropertyID'] : dieWithError(400);

$filterID = (($GLOBALS['RS_POST']['filterID'] == "") ? ("0") : ($GLOBALS['RS_POST']['filterID']));

$allowedItemTypes = array();
if (isset($GLOBALS['RS_POST']['allowedItemTypeIDs']) && $GLOBALS['RS_POST']['allowedItemTypeIDs'] != 0) {
  $allowedItemTypes = explode(",", $GLOBALS['RS_POST']['allowedItemTypeIDs']);
}

if ($clientID != 0 && $clientID != "") {
  if ($itemTypeID != 0 && $itemTypeID != "") {
    if ($parentID != "") {
      if ($parentPID != "") {
        $parentItemTypeID = getClientPropertyReferredItemType($parentPID, $clientID);
        if ($parentID != 0) {
          //check parent exists
          if ($parentItemTypeID != 0) {
            if (count(getItems($parentItemTypeID, $clientID, true, $parentID)) == 0) {
              $results['result'] = "NOK";
              $results['description'] = "INVALID PARENT";
              // Return error and end execution
              RSReturnArrayResults($results);
              exit();
            }
          } else {
            $results['result'] = "NOK";
            $results['description'] = "INVALID PARENT PROPERTY";
            // Return error and end execution
            RSReturnArrayResults($results);
            exit();
          }
        }

        // prepare the propertiesValues array
        $propertiesValues = array();

        // add to the propertiesValues array
        if ($parentPID != 0) {
          $propertiesValues[] = array('ID' => $parentPID, 'value' => $parentID);
          $itemID = createItem($clientID, $propertiesValues);
        } else {
          // Create an item with the default values
          $itemID = createItem($clientID, array(), $itemTypeID);
        }

        if ($itemTypeID == getFilterItemType($clientID, $filterID)) {
          //check filtered
          $filtered = 0;
          $filteredItems = filterItems($clientID, $itemTypeID, $filterID, "");
          foreach ($filteredItems as $filteredItem) {
            if ($filteredItem['ID'] == $itemID) {
              $filtered = 1;
              $myItem = $filteredItem;
            }
          }

          //get get additional props
          $additionalProps = "";
          if ($filtered == 0) {
            //item not in filter, so get additional props
            $properties = getFilterProperties($clientID, $filterID);
            foreach ($properties as $property) {
              $additionalProps .= base64_encode(getClientPropertyName($property["conditionPropertyID"], $clientID)) . "," . base64_encode(getItemPropertyValue($itemID, $property["conditionPropertyID"], $clientID)) . ";";
            }
          } else {
            foreach ($myItem as $property => $value) {
              if ($property != "ID" && $property != "MAINPROP") {
                $additionalProps .= base64_encode($property) . "," . base64_encode($value) . ";";
              }
            }
          }
          $additionalProps = rtrim($additionalProps, ";");
        } else {
          $filtered = 1;
          $additionalProps = "";
        }

        $results['result'] = "OK";
        $results['nodeID'] = $itemID;
        $results['nodeItemType'] = $itemTypeID;
        $results['name'] = getMainPropertyValue($itemTypeID, $itemID, $clientID);
        $results['parentID'] = $parentID;
        $results['parentPropertyID'] = $parentPID;
        $results['parentItemType'] = $parentItemTypeID;
        $results['extraColumns'] = $additionalProps;
      } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID PARENT PROPERTY";
      }
    } else {
      $results['result'] = "NOK";
      $results['description'] = "INVALID PARENT";
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
