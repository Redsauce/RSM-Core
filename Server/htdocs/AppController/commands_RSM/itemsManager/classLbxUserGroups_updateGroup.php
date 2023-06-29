<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

//First of all, we need to check if the variable clientID does not have the value 0
if ($GLOBALS['RS_POST']['clientID'] != 0) {
    //We check if the group exists into the client
    $theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID =".$GLOBALS['RS_POST']['groupID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

    if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
        echo $theQuery_groupValidation;
    }

    $resultGroupsOK = RSQuery($theQuery_groupValidation);

    if ($resultGroupsOK->fetch_array() != 0) {

        //We check if the group name already exists
        $theQuery_groupAlreadyExists = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID <> ".$GLOBALS['RS_POST']['groupID']." AND RS_NAME ='".base64_decode($GLOBALS['RS_POST']['groupName'])."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

        if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
            echo $theQuery_groupAlreadyExists;
        }

        $result = RSQuery($theQuery_groupAlreadyExists);
        if ($result->fetch_array() != 0) {
            RSReturnError("GROUP ALREADY EXISTS", "6");
        }

        // update the group name
        $theQuery = "UPDATE rs_groups SET RS_NAME='".base64_decode($GLOBALS['RS_POST']['groupName'])."' WHERE RS_GROUP_ID=".$GLOBALS['RS_POST']['groupID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];

        if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
            echo $theQuery;
        }
        $result = RSQuery($theQuery);
        $results['result'] = "OK";
        $results['groupName'] = base64_decode($GLOBALS['RS_POST']['groupName']);
    } else {
        RSReturnError("ERROR WHILE UPDATING GROUP", "7");
    }
} else {
    $results['result'] = "NOK";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
