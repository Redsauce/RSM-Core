<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if (($GLOBALS['RS_POST']['clientID'] != 0) && ($GLOBALS['RS_POST']['userID'] != 0)) {
    $theQuery = "SELECT DISTINCT rs_properties_groups.RS_PROPERTY_ID as propertyID FROM rs_properties_groups WHERE rs_properties_groups.RS_GROUP_ID IN ( SELECT RS_GROUP_ID FROM rs_users_groups WHERE RS_USER_ID =". $GLOBALS['RS_POST']['userID'] ." AND RS_CLIENT_ID=". $GLOBALS['RS_POST']['clientID'].")";

    if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
        echo $theQuery;
    }
    $results = RSQuery($theQuery);
} else {
    $results['result'] = "NOK";
}

// And write XML Response back to the application
RSReturnQueryResults($results);
