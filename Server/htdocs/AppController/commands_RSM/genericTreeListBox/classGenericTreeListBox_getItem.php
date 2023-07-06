<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Definitions
$clientID         =   $GLOBALS['RS_POST']['clientID'];
$itemTypeID       =   $GLOBALS['RS_POST']['itemTypeID'];
$itemID           =   $GLOBALS['RS_POST']['itemID'];
$parentPropertyID =   $GLOBALS['RS_POST']['parentPropertyID'];

$filterID         = (($GLOBALS['RS_POST']['filterID'] == "") ? ("0") : ($GLOBALS['RS_POST']['filterID']));

$itemTypeID = parseITID($itemTypeID, $clientID);

if ($clientID != 0 && $clientID != "") {
  if ($itemTypeID != 0 && $itemTypeID != "") {
    if ($itemTypeID == getFilterItemType($clientID, $filterID)) {
      // Get get additional props
      $additionalProps = "";
      // Item not in filter, so get additional props
      $properties = getFilterProperties($clientID, $filterID);

      foreach ($properties as $property) {
        $additionalProps .= base64_encode(getClientPropertyName($property["conditionPropertyID"], $clientID)) . "," . base64_encode(getItemPropertyValue($itemID, $property["conditionPropertyID"], $clientID)) . ";";
      }

      $additionalProps = rtrim($additionalProps, ";");
    } else {
      $additionalProps = "";
    }

    $myItem = getItems($itemTypeID, $clientID, false, $itemID);

    if (!empty($myItem)) {

      if ($parentPropertyID != "") {
        $parentID = getItemPropertyValue($itemID, $parentPropertyID, $clientID);
        if ($parentID == "") {
          $parentID = "0";
        }

        $parentItemTypeID = getClientPropertyReferredItemType($parentPropertyID, $clientID);
      } else {
        $parentID = "0";
        $parentItemTypeID = "";
      }

      $results['result'] = "OK";
      $results['nodeID'] = $itemID;
      $results['nodeItemType'] = $itemTypeID;
      $results['name'] = $myItem[0]['mainValue'];
      $results['parentID'] = $parentID;
      $results['parentPropertyID'] = $parentPropertyID;
      $results['parentItemType'] = $parentItemTypeID;
      $results['extraColumns'] = $additionalProps;
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
RSreturnArrayResults($results);
