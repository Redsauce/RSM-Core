<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemTypeID"]) ? $itemTypeID = $GLOBALS["RS_POST"]["itemTypeID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["textFilter"]) ? $textFilter = $GLOBALS["RS_POST"]["textFilter"] : dieWithError(400);

//First of all, we need to check if the variable clientID does not have the value 0
if ($clientID != 0 && $RSuserID != 0) {
    $filteredResults = getItemIDsRelatedWithItemIDusingFilter($clientID, $RSuserID, $itemTypeID, $textFilter);
    //$filteredResults = filterItems($clientID, $itemTypeID, 0, $textFilter, 0, "MAIN");
    // And write XML Response back to the application
    RSReturnArrayQueryResults($filteredResults, false);
} else {
    $results['result'] = "NOK";
    RSReturnArrayResults($results);
}
