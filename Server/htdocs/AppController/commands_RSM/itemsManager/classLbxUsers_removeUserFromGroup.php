<?php
//***************************************************
//Description:
//  Remove the user from a group
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMusersManagement.php";


if ($GLOBALS['RS_POST']['clientID'] != 0) {
    // check if the user exists into the client
    $theQuery_userValidation = 'SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID = ' . $GLOBALS['RS_POST']['userID'] . ' AND RS_CLIENT_ID = ' . $GLOBALS['RS_POST']['clientID'];
    $theQuery_groupValidation = 'SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID = ' . $GLOBALS['RS_POST']['groupID'] . ' AND RS_CLIENT_ID = ' . $GLOBALS['RS_POST']['clientID'];

    $resultUserOK = RSquery($theQuery_userValidation);
    $resultGroupOK = RSquery($theQuery_groupValidation);

    if (($resultUserOK->num_rows != 0) && ($resultGroupOK->num_rows != 0)) {
        //The users exists, so perform the action
        $results["result"] = removeUserFromGroup($GLOBALS['RS_POST']['userID'], $GLOBALS['RS_POST']['clientID'], $GLOBALS['RS_POST']['groupID']);
    } else {
        $results["result"] = "NOK";
    }
} else {
    $results["result"] = "NOK";
}

// And write XML Response back to the application
RSreturnArrayResults($results);
