<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

// Parameters validation
isset($GLOBALS[$cstRS_POST]['clientID'    ]) ? $clientID       =               $GLOBALS[$cstRS_POST]['clientID'    ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['itemTypeID'  ]) ? $itemTypeID     =               $GLOBALS[$cstRS_POST]['itemTypeID'  ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['parentID'    ]) ? $parentID       =               $GLOBALS[$cstRS_POST]['parentID'    ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['propertyID'  ]) ? $propertyID     =               $GLOBALS[$cstRS_POST]['propertyID'  ]  : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['ids'         ]) ? $idList         = explode(",",  $GLOBALS[$cstRS_POST]['ids'         ]) : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['orders'      ]) ? $orderList      = explode(",",  $GLOBALS[$cstRS_POST]['orders'      ]) : dieWithError(400);


// Execute and write XML Response back to the application
RSReturnArrayResults(reorderItems($clientID, $itemTypeID, $propertyID, $parentID, $idList, $orderList));

?>
