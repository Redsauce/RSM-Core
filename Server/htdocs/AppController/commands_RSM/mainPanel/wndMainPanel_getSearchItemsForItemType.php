<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

isset($GLOBALS[$cstRS_POST]["clientID"  ]) ? $clientID   = $GLOBALS[$cstRS_POST]["clientID"  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstItemTypeID]) ? $itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["textFilter"]) ? $textFilter = $GLOBALS[$cstRS_POST]["textFilter"] : dieWithError(400);

//First of all, we need to check if the variable clientID does not have the value 0
if ($clientID != 0 AND $RSuserID != 0) {
    $filteredResults = getItemIDs_RelatedWith_ItemID_usingFilter($clientID,$RSuserID,$itemTypeID,$textFilter);
    //$filteredResults = filterItems($clientID, $itemTypeID, 0, $textFilter, 0, "MAIN");
    // And write XML Response back to the application
    RSReturnArrayQueryResults($filteredResults, false);
} else{
    $results['result'] = "NOK";
    RSReturnArrayResults($results);
}
