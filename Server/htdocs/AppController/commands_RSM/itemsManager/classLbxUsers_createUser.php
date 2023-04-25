<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMbadgesManagement.php";

$clientID =               $GLOBALS['RS_POST']['clientID'] ;
$login    = base64_decode($GLOBALS['RS_POST']['login'   ]);
$password =               $GLOBALS['RS_POST']['password'] ;
$personID =               $GLOBALS['RS_POST']['personID'] ;
$badge    =               $GLOBALS['RS_POST']['badge'   ] ;

// First of all, we need to verify if a badge is comming
if ($badge == "") {
    $badge = RSgetUniqueBadge($clientID);

} else {
    $badgeExists = RSbadgeExist($badge, $clientID);
    if ($badgeExists == true) {
        RSReturnError("ERROR CREATING USER. BADGE ALREADY EXISTS FOR THIS CLIENT.", "1");
        exit;
    }
}

// Second step, we need to check if the variable clientID has a value
if (isset($clientID)) {
    
    //We check if the user already exists for the given client
    $theQuery_userAlreadyExists = 'SELECT RS_USER_ID FROM rs_users WHERE RS_LOGIN ="' . $login . '" AND RS_CLIENT_ID = ' . $clientID;
    $result = RSQuery($theQuery_userAlreadyExists);

    if ($result->num_rows > 0) {
        RSReturnError("QUERY ERROR CREATING USER.", "2");
        exit;

    } else {

        if (empty($personID)) {

            // Get staff item type
            $staffItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID);

            // Add new entry
            $personID = createEmptyItem($staffItemTypeID, $clientID);

            // Update main value with the login (TODO: the main value of the items "staff" may not be the name or an identifier name... so we could add an application property called "name" to the staff itemtype and use it for saving login into the item entry)
            setPropertyValueByID(getMainPropertyID($staffItemTypeID, $clientID), $staffItemTypeID, $personID, $clientID, $login, '', $RSuserID);
        }

        // Insert user into rs_users table
        $newID = getNextIdentification("rs_users", "RS_USER_ID", $clientID);
        $theQueryUser = 'INSERT INTO rs_users (RS_USER_ID, RS_CLIENT_ID, RS_LOGIN, RS_PASSWORD, RS_ITEM_ID, RS_BADGE) VALUES (' . $newID . ',' . $clientID . ',"' . $login . '","' . $password . '",' . $personID . ',"' . $badge . '")';
    }

    // Execute the query
    if ($result = RSQuery($theQueryUser)) {
        $results['userID'  ] = $newID;
        $results['login'   ] = $login;
        $results['personID'] = $personID;
        $results['badge'   ] = $badge;

    } else {
        RSReturnError("QUERY ERROR CREATING USER.", "3");
    }

} else {
    RSReturnError("ERROR CREATING USER. INVALID CLIENTID.", "4");
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
