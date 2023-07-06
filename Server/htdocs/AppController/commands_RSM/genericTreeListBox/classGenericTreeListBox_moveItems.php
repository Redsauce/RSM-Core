<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMfiltersManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$IDs = explode(";", $GLOBALS['RS_POST']['IDs']);
$parentID = $GLOBALS['RS_POST']['parentID'];


if ($clientID != 0 && $clientID != "") {
  if ($parentID != "") {

    //begin transaction
    $mysqli->begin_transaction();

    foreach ($IDs as $ID) {
      $item = explode(",", $ID);
      $itemID = $item[0];
      $itemTypeID = $item[1];
      $parentPropertyID = $item[2];

      if ($itemTypeID != 0 && $itemTypeID != "") {
        if ($itemID != 0 && $itemID != "") {
          if ($parentPropertyID != "") {
            if (isPropertyVisible($RSuserID, $parentPropertyID, $clientID)) {
              if ($parentID != 0) {
                //check parent exists
                $parentItemTypeID = getClientPropertyReferredItemType($parentPropertyID, $clientID);
                if ($parentItemTypeID != 0) {
                  if (count(getItems($parentItemTypeID, $clientID, true, $parentID)) == 0) {
                    $results['result'] = "NOK";
                    $results['description'] = "INVALID PARENT";
                    // Return error and end execution
                    RSreturnArrayResults($results);
                    exit();
                  }
                } else {
                  $results['result'] = "NOK";
                  $results['description'] = "INVALID PARENT PROPERTY";
                  // Return error and end execution
                  RSreturnArrayResults($results);
                  exit();
                }
              }
              if ($parentPropertyID != 0) {
                // Update the item parent
                $result = setPropertyValueByID($parentPropertyID, $itemTypeID, $itemID, $clientID, $parentID, '', $RSuserID);

                if ($result < 0) {
                  //rollback transaction
                  $mysqli->rollback();
                  $results['result'] = 'NOK';
                  switch ($result) {
                    case -1:
                      $results['description'] = 'RECURSIVE MOVE NOT ALLOWED';
                      break;
                    case -2:
                      $results['description'] = 'INVALID USER';
                      break;
                    case -3:
                      $results['description'] = 'ERROR MOVING ITEM';
                      break;
                    default:
                      $results['description'] = 'UNKNOWN ERROR';
                      break;
                  }
                  break;
                }
              } else {
                $results['result'] = "NOK";
                $results['description'] = "CAN NOT MOVE AN ITEM WITHOUT PARENT PROPERTY";
                break;
              }
            } else {
              $results['result'] = "NOK";
              $results['description'] = "NOT ENOUGH PERMISSIONS TO MOVE ITEM";
              break;
            }
          } else {
            $results['result'] = "NOK";
            $results['description'] = "INVALID PARENT PROPERTY";
            break;
          }
        } else {
          $results['result'] = "NOK";
          $results['description'] = "INVALID ITEM";
          break;
        }
      } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID ITEMTYPE";
        break;
      }
    }
    if (isset($results['result']) && $results['result'] == "NOK") {
      //rollback transaction
      $mysqli->rollback();
    } else {
      //commit transaction
      $mysqli->commit();
      $results['result'] = 'OK';
    }
  } else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID PARENT";
  }
} else {
  $results['result'] = "NOK";
  $results['description'] = "INVALID CLIENT";
}

// Return results
RSreturnArrayResults($results);
