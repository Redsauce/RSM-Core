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
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $clientID   = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemIDs'   ]) ? $itemIDs    = $GLOBALS[$cstRS_POST]['itemIDs'   ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken    = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken = "";

$itemTypeID = ParseITID($itemTypeID, $clientID);

// Para eliminar un item primero tenemos que comprobar que tenga permiso
// de borrado para cada una de sus propiedades propiedades
$propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
  if ($itemIDs != '' && !RSitemsMatchTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemIDs)) {
    $results['result'] = 'NOK';
    $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THESE ITEMS';
  } elseif ($itemIDs != '') {
    deleteItems($itemTypeID, $clientID, $itemIDs);
    $results['result'] = 'OK';
  } else {
    $results['result'] = 'OK';
  }
} else {
  $results['result'] = 'NOK';
  $results['description'] = 'YOU DONT HAVE PERMISSIONS TO DELETE THIS ITEM';
}

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
