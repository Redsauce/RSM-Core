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
require_once "../utilities/RStools.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// definitions
isset($GLOBALS[$cstRS_POST]["clientID"  ]) ? $clientID     = $GLOBALS[$cstRS_POST]["clientID"  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID   = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["itemID"    ]) ? $itemID       = $GLOBALS[$cstRS_POST]["itemID"    ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken ]) ? $RStoken      = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken = "";

$itemTypeID = ParseITID($itemTypeID, $clientID);

// Para eliminar un item primero tenemos que comprobar que tenga permiso
// de borrado para cada una de sus propiedades propiedades
$propertiesList = getClientItemTypePropertiesId($itemTypeID, $clientID);

if ((RShasTokenPermissions($RStoken, $propertiesList, "DELETE")) || (arePropertiesVisible($RSuserID, $propertiesList, $clientID))) {
    deleteItem($itemTypeID, $itemID, $clientID);
    $results['result'] = 'OK';
} else {
    $results['result'] = 'NOK';
    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO DELETE THIS ITEM';
}

// And write XML Response back to the application without compression
RSReturnArrayResults($results, false);
