<?php
//***************************************************
//Description:
//	Returns the client's item property associated with the application's item property
//***************************************************

require_once "../utilities/RSdatabase.php";

// Query the database
$results = RSQuery("SELECT RS_PROPERTY_ID AS 'propertyID' FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = '".$GLOBALS[$cstRS_POST][$cstPropertyAppID]."' AND RS_CLIENT_ID = '".$GLOBALS[$cstRS_POST][$cstClientID]."'");

// And write XML Response back to the application
RSReturnQueryResults($results);
