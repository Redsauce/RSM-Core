<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if ($GLOBALS['RS_POST']['clientID'] != 0) {

    //We check if the group exists into the client
    $theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='" . $GLOBALS['RS_POST']['groupID'] . "' AND RS_CLIENT_ID=" . $GLOBALS['RS_POST']['clientID'];
    $resultgroupOK = RSquery($theQuery_groupValidation);

    if ($resultgroupOK->num_rows != 0) {

        //The group exists, so perform the action
        $theQuery = "SELECT rs_users_groups.RS_USER_ID as 'userID', rs_users_groups.RS_GROUP_ID as 'ID' FROM rs_users_groups INNER JOIN rs_groups ON rs_users_groups.RS_GROUP_ID = rs_groups.RS_GROUP_ID AND rs_users_groups.RS_CLIENT_ID = rs_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_GROUP_ID =" . $GLOBALS['RS_POST']['groupID'] . " AND rs_users_groups.RS_CLIENT_ID=" . $GLOBALS['RS_POST']['clientID'];

        // Query the database
        $results = RSquery($theQuery);
        RSreturnQueryResults($results);
    } else {

        $results["result"] = "NOK";
        // And write XML Response back to the application
        RSreturnArrayResults($results);
    }
} else {
    $results["result"] = "NOK";
    // And write XML Response back to the application
    RSreturnArrayResults($results);
}
