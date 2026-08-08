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
    global $cstRS_POST;
    if (!isset($GLOBALS[$cstRS_POST]['RSbuild'   ])) return -1;
    if (!isset($GLOBALS[$cstRS_POST]['RSplatform'])) return -1;
    if (!isset($GLOBALS[$cstRS_POST]['RSappName' ])) return -1;

    $theQuery = "SELECT `RS_ID` FROM `rs_versions` WHERE `RS_BUILD`='" . $GLOBALS[$cstRS_POST]['RSbuild'] . "' AND `RS_OS`= '" . $GLOBALS[$cstRS_POST]['RSplatform'] . "' AND `RS_NAME` ='" . $GLOBALS[$cstRS_POST]['RSappName'] . "'";

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
    // If we return -1: The input is invalid or there was an error executing the query
    // If we return 0: RSM could not match the provided data to a single user in a single customer
    // If we return an integer: This is the ID of the user for the passed clientID
    
    global $cstRS_POST;
    global $cstClientID;
    global $mysqli;

    // A login is always required, both for password and badge authentication.
    if (!isset($GLOBALS[$cstRS_POST]['RSLogin'])) {
        error_log('RSCheckUserAccess: RSLogin is missing');
        return 0;
    }

    // Validate the client before sending it to MySQL. Apart from rejecting malformed
    // requests, this guarantees that the authenticated user is checked against one
    // concrete customer and cannot select another client through crafted SQL input.
    if (!isset($GLOBALS[$cstRS_POST][$cstClientID])
        || !is_numeric($GLOBALS[$cstRS_POST][$cstClientID])
        || intval($GLOBALS[$cstRS_POST][$cstClientID]) <= 0) {
        error_log('RSCheckUserAccess: clientID is missing or invalid');
        return -1;
    }

    $login = $GLOBALS[$cstRS_POST]['RSLogin'];
    $clientID = intval($GLOBALS[$cstRS_POST][$cstClientID]);

    try {
        // When a password is supplied, authenticate with the RSM login and its stored
        // MD5 password. Prepared parameters keep credentials separate from the SQL.
        if (isset($GLOBALS[$cstRS_POST]['RSuserMD5Password']) && $GLOBALS[$cstRS_POST]['RSuserMD5Password'] != "") {
            $password = $GLOBALS[$cstRS_POST]['RSuserMD5Password'];
            $stmt = $mysqli->prepare('SELECT RS_USER_ID FROM rs_users WHERE RS_LOGIN = ? AND RS_PASSWORD = ? AND RS_CLIENT_ID = ?');
            $stmt->bind_param('ssi', $login, $password, $clientID);
        } else {
            // If no password is supplied, preserve the existing badge authentication
            // behavior: RSLogin contains the badge value to look up for this client.
            $stmt = $mysqli->prepare('SELECT RS_USER_ID FROM rs_users WHERE RS_BADGE = ? AND RS_CLIENT_ID = ?');
            $stmt->bind_param('si', $login, $clientID);
        }

        // Buffer the result so num_rows can verify that the credentials identify one
        // and only one user. No match or an ambiguous match must fail closed.
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1) {
            error_log('RSCheckUserAccess: credentials matched ' . intval($stmt->num_rows) . ' users for clientID ' . $clientID);
            $stmt->close();
            return 0;
        }

        // Return the authenticated user's numeric ID to the authorization layer.
        $stmt->bind_result($userID);
        $stmt->fetch();
        $stmt->close();
        return intval($userID);
    } catch (mysqli_sql_exception $exception) {
        // Database errors are not authentication failures caused by bad credentials.
        // Return -1 so callers can reject the request and distinguish this condition.
        error_log('RSCheckUserAccess: database error for clientID ' . $clientID . ': ' . $exception->getMessage());
        return -1;
    }
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
