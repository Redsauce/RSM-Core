<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if ($GLOBALS['RS_POST']['clientID'] != 0) {
    //We check if the user exists into the client

    $theQuery_userValidation = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID ='" . $GLOBALS['RS_POST']['userID'] . "' AND RS_CLIENT_ID=" . $GLOBALS['RS_POST']['clientID'];
    $resultUserOK = RSquery($theQuery_userValidation);

    if ($resultUserOK->fetch_array() != 0) {
        //The users exists, so perform the action
        //$theQuery = "SELECT rs_groups.RS_ID AS 'groupID' FROM rs_groups WHERE rs_groups.RS_ID IN ( SELECT rs_users_groups.RS_GROUP_ID AS 'groupID' FROM rs_users_groups, rs_clients_users, rs_groups WHERE rs_users_groups.RS_USER_ID =".$GLOBALS['RS_POST']['userID']." AND rs_users_groups.RS_USER_ID = rs_clients_users.RS_USER_ID AND rs_clients_users.RS_CLIENT_ID =".$GLOBALS['RS_POST']['clientID']." AND rs_groups.RS_CLIENT_ID =".$GLOBALS['RS_POST']['clientID'].") AND rs_groups.RS_DELETED=0 AND rs_groups.RS_CLIENT_ID =".$GLOBALS['RS_POST']['clientID'];
        $theQuery = "SELECT rs_groups.RS_GROUP_ID AS 'groupID' FROM rs_groups INNER JOIN rs_users_groups  ON (rs_groups.RS_GROUP_ID=rs_users_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_users_groups.RS_CLIENT_ID) WHERE rs_users_groups.RS_USER_ID = " . $GLOBALS['RS_POST']['userID'] . " AND rs_groups.RS_CLIENT_ID =" . $GLOBALS['RS_POST']['clientID'];
    } else {
        $results["result"] = "NOK";
    }
} else {
    $results["result"] = "NOK";
}

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSreturnQueryResults($results);
