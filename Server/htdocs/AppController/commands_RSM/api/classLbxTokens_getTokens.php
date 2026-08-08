<?php
// ***************************************************************************************
// Description:
//     Returns the list of tokens pertaining to a client with their corresponding IDs
// 
// Parameters
//   The only needed parameter is the clientID that is already passed in every petition
// ***************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

$clientID = RSrequireTokenManagementAccess();

// Now we build the query
$results = RStokensFromClient($clientID);

// And write XML Response back to the application
RSReturnQueryResults($results);
