<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

// And write XML Response back to the application
RSreturnQueryResults(getLists($GLOBALS['RS_POST']['clientID']));
