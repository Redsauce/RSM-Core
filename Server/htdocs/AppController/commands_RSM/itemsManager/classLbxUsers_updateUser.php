<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMdefinitions.php";
require_once "../utilities/RSMbadgesManagement.php";

isset($GLOBALS['RS_POST']['password']) ? $password = $GLOBALS['RS_POST']['password'] : $password = "";
isset($GLOBALS['RS_POST']['badge']) ? $badge    = base64_decode($GLOBALS['RS_POST']['badge']) : $badge = RScreateBadge($GLOBALS['RS_POST']['clientID']);
$login    = base64_decode($GLOBALS['RS_POST']['login']);
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID   = $GLOBALS['RS_POST']['userID'];
$staffID  = $GLOBALS['RS_POST']['staffID'];


// Check that the received clientID is a valid number
if ($clientID > 0) {

    // Check that the user we want to modify exists.
    $theQuery_userExists = "SELECT RS_USER_ID, RS_ITEM_ID, RS_PASSWORD, RS_LOGIN, RS_BADGE FROM rs_users WHERE RS_USER_ID = " . $userID . " AND RS_CLIENT_ID = " . $clientID;
    $resultUsers = RSquery($theQuery_userExists);

    if ($resultUsers->num_rows > 0) {

        // Check if the login is already associated with another user from this client.
        $theQuery_loginAlreadyExists = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID <> " . $userID . " AND RS_LOGIN ='" . $login . "' AND RS_CLIENT_ID = " . $clientID;
        $result = RSquery($theQuery_loginAlreadyExists);

        if ($result->fetch_array() != 0) {
            RSreturnError("ERROR UPDATING USER. THE LOGIN-EMAIL ALREADY EXISTS IN THIS CUSTOMER.", "1");
        }

        // Check if the badge is already associated with another user from this client.
        $theQuery_badgeAlreadyExists = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID <> " . $userID . " AND RS_BADGE ='" . $badge . "' AND RS_CLIENT_ID = " . $clientID;
        $result = RSquery($theQuery_badgeAlreadyExists);

        if ($result->fetch_array() != 0) {
            RSreturnError("ERROR UPDATING USER. THE BADGE ALREADY EXISTS IN THIS CUSTOMER.", "2");
        }

        // If the user creation request does not have an associated staff, we create a new staff element.
        if ($staffID == '0') {

            // Get staff item type
            $staffItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID);

            // Add new entry
            $staffID = createEmptyItem($staffItemTypeID, $clientID);

            // Update the system property 'staff.Name' with the login-email
            setPropertyValueByID($definitions['staffName'], $staffItemTypeID, $staffID, $clientID, $login, '', $RSuserID);
        }

        // Build the query base
        $theQuery = "UPDATE rs_users SET RS_LOGIN = '" . $login . "'";

        // Check if the password, badge or staffID have changed.
        // Previous values of the user's properties before their modification.
        $row = $resultUsers->fetch_assoc();
        $passwordBeforeChange = $row["RS_PASSWORD"];
        $badgeBeforeChange    = $row["RS_BADGE"];
        $staffIDBeforeChange  = $row["RS_ITEM_ID"];

        $passwordChanged = ($password != "" && $passwordBeforeChange != $password) ? true : false;
        $badgeChanged    = ($badgeBeforeChange    != $badge) ? true : false;
        $staffIDChanged  = ($staffIDBeforeChange  != $staffID) ? true : false;

        // Add password change if needed
        if ($passwordChanged) {
            $theQuery .= ", RS_PASSWORD = '" . $password . "'";
        }

        $theQuery .= ", RS_ITEM_ID = " . $staffID;

        // Add badge change if needed
        if ($badgeChanged) {
            $theQuery .= ", RS_BADGE = '" . $badge . "'";
        }

        $theQuery .= " WHERE RS_USER_ID = " . $userID . " AND RS_CLIENT_ID = " . $clientID;

        // Execute the query
        if ($result = RSquery($theQuery)) {
            $results['result'] = "OK";

            // We only return the values that have changed from their original values.
            if ($passwordChanged) {
                $results['password'] = $password;
            }
            if ($badgeChanged) {
                $results['badge'] = $badge;
            }
            if ($staffIDChanged) {
                $results['staffID'] = $staffID;
            }
        } else {
            RSreturnError("QUERY ERROR UPDATING USER", "3");
        }
    } else {
        RSreturnError("QUERY ERROR UPDATING USER. USER TO UPDATE NOT FOUND.", "4");
    }
} else {
    RSreturnError("ERROR UPDATING USER. INVALID CLIENT NUMBER.", "5");
}

// And write XML Response back to the application
RSreturnArrayResults($results);
