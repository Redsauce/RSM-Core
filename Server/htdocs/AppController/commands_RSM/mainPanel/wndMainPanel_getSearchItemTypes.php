<?php
//***************************************************//
//Description:
//  This PHP receives a textFilter and returns an array with the names.
//  and identifiers of the itemTypes where the textFilter appears,
//  and the number of occurrences.
//  Only the allowed item types for the user are returned.
//***************************************************//

// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["textFilter"]) ? $textFilter = $GLOBALS["RS_POST"]["textFilter"] : dieWithError(400);

//First of all, we need to check if the variable clientID does not have the value 0
if ($clientID != 0 && $RSuserID != 0) {
  $filteredResults = getItemTypeIDs_usingFilter($RSuserID, $clientID, $textFilter);

  // Write XML Response back to the application
  RSreturnArrayQueryResults($filteredResults, false);
} else {
  $results['result'] = "NOK";
  RSreturnArrayResults($results);
}
