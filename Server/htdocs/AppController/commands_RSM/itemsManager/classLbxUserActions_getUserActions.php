<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMusersManagement.php";

RSReturnQueryResults(getUserActions($GLOBALS[$cstRS_POST][$cstUserID], $GLOBALS[$cstRS_POST][$cstClientID]));
