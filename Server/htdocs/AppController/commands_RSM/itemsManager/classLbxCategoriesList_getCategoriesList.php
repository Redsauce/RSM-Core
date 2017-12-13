<?php
//***************************************************
//Description:
//	Returns all the categories for the given itemtype
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT `RS_CATEGORY_ID` as 'ID', `RS_NAME` as 'NAME' FROM `rs_categories` WHERE `RS_CLIENT_ID`='" . $GLOBALS['RS_POST']["clientID"] . "' AND `RS_ITEMTYPE_ID`='" . $GLOBALS['RS_POST']["itemtypeID"] . "' ORDER BY `RS_ORDER`";

// Query the database
$results = RSQuery($theQuery);

// And write XML Response back to the application
RSReturnQueryResults($results);
?>
