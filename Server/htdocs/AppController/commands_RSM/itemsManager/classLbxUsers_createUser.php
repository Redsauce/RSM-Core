<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMbadgesManagement.php";

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : $clientID = "";
isset($GLOBALS["RS_POST"]["badge"]) ? $badge    = base64_decode($GLOBALS["RS_POST"]["badge"]) : $badge    = "";
$login    = base64_decode($GLOBALS['RS_POST']['login']);
$password =               $GLOBALS['RS_POST']['password'];
$staffID  =               $GLOBALS['RS_POST']['staffID'];

if ($clientID != "") {

    // Generate a badge if needed
    if ($badge == "") {
        $badge = RScreateBadge($clientID);
    } else {
        $badgeExists = RSbadgeExists($badge, $clientID);
        if ($badgeExists) {
            RSreturnError("ERROR CREATING USER. BADGE ALREADY EXISTS FOR THIS CLIENT.", "1");
        }
    }

    // We check if the user already exists for the given client
    $theQuery_userAlreadyExists = 'SELECT RS_USER_ID FROM rs_users WHERE RS_LOGIN ="' . $login . '" AND RS_CLIENT_ID = ' . $clientID;
    $result = RSquery($theQuery_userAlreadyExists);

    if ($result->num_rows > 0) {
        RSreturnError("QUERY ERROR CREATING USER. USER ALREADY EXISTS.", "2");
    }

    // If the user creation request does not have an associated staff, we create a new staff element.
    if (empty($staffID)) {

        // Get staff item type
        $staffItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['staff'], $clientID);

        // Add new entry
        $staffID = createEmptyItem($staffItemTypeID, $clientID);

        // Update the system property 'staff.Name' with the login-email
        setPropertyValueByID($definitions['staffName'], $staffItemTypeID, $staffID, $clientID, $login, '', $RSuserID);
    }

    // Insert user into rs_users table
    $newID = getNextIdentification("rs_users", "RS_USER_ID", $clientID);
    $theQueryUser = 'INSERT INTO rs_users (RS_USER_ID, RS_CLIENT_ID, RS_LOGIN, RS_PASSWORD, RS_ITEM_ID, RS_BADGE) VALUES (' . $newID . ',' . $clientID . ',"' . $login . '","' . $password . '",' . $staffID . ',"' . $badge . '")';

    // Execute the query
    if ($result = RSquery($theQueryUser)) {

        $createdUser['userID'] = $newID;
        $createdUser['staffID'] = $staffID;

        // And write XML Response back to the application
        RSreturnArrayResults($createdUser);
    } else {
        RSreturnError("QUERY ERROR CREATING USER.", "3");
    }
} else {
    RSreturnError("ERROR CREATING USER. INVALID CLIENTID.", "4");
}
