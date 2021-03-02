<?php
//*****************************************************************************
//Description:
//    Deletes an item of the specified itemType with the associated values
//
//  PARAMETERS:
//  itemTypeID: ID of the itemType to delete
//      itemID: ID of the item to delete
//*****************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// definitions
isset($GLOBALS['RS_POST']['clientID'  ]) ? $clientID   = $GLOBALS['RS_POST']['clientID'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID']) ? $itemTypeID = $GLOBALS['RS_POST']['itemTypeID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['itemIDs'   ]) ? $itemIDs    = $GLOBALS['RS_POST']['itemIDs'   ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken'   ]) ? $RStoken    = $GLOBALS['RS_POST']['RStoken'   ] : $Rstoken = "";

$itemTypeID = ParseITID($itemTypeID, $clientID);

// Para eliminar un item primero tenemos que comprobar que tenga permiso
// de borrado para cada una de sus propiedades propiedades
$propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
  if ($itemIDs != '') {
    deleteItems($itemTypeID, $clientID, $itemIDs);
  }
  $results['result'] = 'OK';
} else {
  $results['result'] = 'NOK';
  $results['description'] = 'YOU DONT HAVE PERMISSIONS TO DELETE THIS ITEM';
}

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
?>
