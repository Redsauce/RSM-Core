<?php
//***************************************************
//RSvalidationFunctions.php
//***************************************************
//Description:
//	Functions to check if the application version is compatible with
//  the database in use, and to check if the user
//  has privileges to work with the system
//***************************************************
//Version:
//***************************************************
//Input: POST
//	         RSuserID: user's login
//	RSuserMD5Password: user's password encrypted in MD5
//            RSbuild: Application build
//          RSappName: Application Name
//         RSlanguage: Application language
//*****************************************************************************
//             /
//            |  1 If current app is compatible with the current database
//		       |  0 If current app is NOT compatible with the current database
// Outputs: <
//           \  1 If user has access to work with the selected database
//            \  0 If user has NOT access to work with the selected database
//             \
//*****************************************************************************

// Check if the current application is compatible with the current database
function RSCheckCompatibleDB($serviceMode) {
    if (!isset($GLOBALS['RS_POST']['RSbuild'   ])) return -1;
    if (!isset($GLOBALS['RS_POST']['RSplatform'])) return -1;
    if (!isset($GLOBALS['RS_POST']['RSappName' ])) return -1;

    $theQuery = "SELECT `RS_ID` FROM `rs_versions` WHERE `RS_BUILD`='" . $GLOBALS['RS_POST']['RSbuild'] . "' AND `RS_OS`= '" . $GLOBALS['RS_POST']['RSplatform'] . "' AND `RS_NAME` ='" . $GLOBALS['RS_POST']['RSappName'] . "'";

    if ($serviceMode == 0) $theQuery = $theQuery . " AND `RS_PUBLIC`=1";

    $versions = RSQuery($theQuery);

    // Check the results
    if (!$versions) return -1;

    // There was an error executing the query
    if ($versions->num_rows == 0) return 0;

    // The application version is not registered against the database so it is incompatible

    // The application is compatible with the database
    return 1;
}


// Check if the current user has access to work with the selected database
function RSCheckUserAccess() {
    // If we rerturn -1: There was an error executing the query
    // If we return 0: RSM could not match the provided data to a single user in a single customer
    // If we return an integer: This is the ID of the user for the passed clientID
    
    if (!isset($GLOBALS['RS_POST']['RSLogin'])) return 0;

    // We don't allow the script to continue execution with an empty clientID since the queries would throw an error
    if ((isset($GLOBALS['RS_POST']['clientID'])) && ($GLOBALS['RS_POST']['clientID'] == "")) return -1;

    if ((isset($GLOBALS['RS_POST']['RSuserMD5Password'])) && ($GLOBALS['RS_POST']['RSuserMD5Password'] != "")) {
        // Continue checking the username and password.
        $theQuery = "SELECT `RS_USER_ID` FROM `rs_users` WHERE `RS_LOGIN`='" . $GLOBALS['RS_POST']['RSLogin'] . "' AND `RS_PASSWORD` ='" . $GLOBALS['RS_POST']['RSuserMD5Password'] . "' AND RS_CLIENT_ID = " . $GLOBALS['RS_POST']['clientID'];

    } else {
        // There is no defined password. Use the login as a badge.
        $theQuery = "SELECT `RS_USER_ID` FROM `rs_users` WHERE `RS_BADGE`='" . $GLOBALS['RS_POST']['RSLogin'] . "' AND RS_CLIENT_ID = " . $GLOBALS['RS_POST']['clientID'];
    }

    $users = RSQuery($theQuery);

    // Check if the query failed
    if (!$users) return -1;

    // User not found or multiple users found
    if ($users->num_rows != 1) return 0;

    // A single user was found with the provided login and password
    $row = $users->fetch_assoc();
    return $row['RS_USER_ID'];
}


// Get the staff associated with a customer's user.
function getUserStaffID($userID, $clientID) {

    $theQuery = "SELECT `RS_ITEM_ID` FROM `rs_users` WHERE `RS_USER_ID`=" . $userID . " AND `RS_CLIENT_ID` = " . $clientID;
    $users = RSQuery($theQuery);

    // Check the results
    if (!$users) return -1;

    // User not found
    if ($users->num_rows != 1) return 0;

    // A single user was found with the userID
    $row = $users->fetch_assoc();
    return $row['RS_ITEM_ID'];
}
?>
