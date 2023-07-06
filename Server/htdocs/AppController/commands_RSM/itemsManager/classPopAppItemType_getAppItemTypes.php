<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

// Now we build the query
$theQuery = "SELECT RS_ID AS 'id', RS_NAME AS 'name' FROM rs_item_type_app_definitions ORDER BY RS_NAME";

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSreturnQueryResults($results);
