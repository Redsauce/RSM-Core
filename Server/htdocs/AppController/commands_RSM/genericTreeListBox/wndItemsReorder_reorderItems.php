<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

// Parameters validation
isset($GLOBALS['RS_POST']['clientID'    ]) ? $clientID       =               $GLOBALS['RS_POST']['clientID'    ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID'  ]) ? $itemTypeID     =               $GLOBALS['RS_POST']['itemTypeID'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentID'    ]) ? $parentID       =               $GLOBALS['RS_POST']['parentID'    ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyID'  ]) ? $propertyID     =               $GLOBALS['RS_POST']['propertyID'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['ids'         ]) ? $idList         = explode(",",  $GLOBALS['RS_POST']['ids'         ]) : dieWithError(400);
isset($GLOBALS['RS_POST']['orders'      ]) ? $orderList      = explode(",",  $GLOBALS['RS_POST']['orders'      ]) : dieWithError(400);


// Execute and write XML Response back to the application
RSReturnArrayResults(reorderItems($clientID, $itemTypeID, $propertyID, $parentID, $idList, $orderList));

?>
