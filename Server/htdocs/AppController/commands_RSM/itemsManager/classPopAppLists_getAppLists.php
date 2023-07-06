<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMlistsManagement.php";

$appLists = getAppLists();

// And write XML Response back to the application
RSreturnArrayQueryResults($appLists);
