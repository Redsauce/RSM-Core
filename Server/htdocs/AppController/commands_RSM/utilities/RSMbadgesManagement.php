<?php
// Database connection startup
require_once "RSdatabase.php";

// Functions in this file related with the use of badges in RSM
// - RSUserFromBadge
// - RScreateBadgeForUser
// - RSgetUniqueBadge
// - RSupdateAllBadgeUsers
// - RSupdateAllBadgeUsers
// - generateRandomString


// -----------------------------
function RSUserFromBadge($RSbadge) {
    $theQuery = "SELECT `RS_USER_ID`, `RS_CLIENT_ID`, `RS_LOGIN` FROM `rs_users`
                WHERE `RS_BADGE` = '" . $RSbadge . "'";
    $result = RSQuery($theQuery);

    return $result;
}

// -----------------------------
function RScountBadge($RSbadge) {
    $results = RSQuery("SELECT COUNT('RS_BADGE') as total
                        FROM rs_users
                        WHERE RS_BADGE = '" . $RSbadge . "'");
    return $results;
}

// -----------------------------
function RSupdateBadgeForUser($userID, $clientID) {
    $uniqueBadge = RSgetUniqueBadge();
    $results = RSQuery("UPDATE rs_users SET RS_BADGE = '".$uniqueBadge."' 
                        WHERE RS_USER_ID = " . $userID . " AND 
                        RS_CLIENT_ID = " . $clientID . ";");
    return $results;
}

// -----------------------------
// This function generates a random string of the given length
function generateRandomString($length = 10) {
    // This is the list of characters allowed inside the generated string
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        // We loop until the desired length is reached, adding random characters
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }

    // And return the result
    return $randomString;
}


// -----------------------------
function RSgetUniqueBadge(){
    /* ***************************************************************************************
    DESCRIPTION
    Create a new badge that does not exist in the list of user badges in the database.

    PARAMETERS
    It does not need any parameters.

    RETURN
    badge: The badge itself, as a 32-character string (MD5 hash)
    ***************************************************************************************
    */


    // We will use this variable in order to control if the new badge exists or not
    $exists = false;

    do {
        // Let's generate a badge
        $badge = md5(generateRandomString(256));

        // Ask the database for badges like the new one
        $results = RScountBadge($badge);

        // Obtain the data from the query
        if ($results) $result = $results->fetch_assoc();
        
        // Check if we found a badge like ours in the database
        if ($result['total'] <> 0) $exists = true; // The badge is already stored in the database. We must generate a new one

    } while ($exists == true);

    // If the execution reaches this point, the badge does not exist so we can return it
    return $badge;


}

// -----------------------------
function RSupdateAllBadgeUsers(){
    $theQuery_users = "SELECT RS_USER_ID, RS_CLIENT_ID FROM rs_users";
    $resultUsers = RSQuery($theQuery_users);

    while($row=$resultUsers->fetch_assoc()){
        RSupdateBadgeForUser($row['RS_USER_ID'], $row['RS_CLIENT_ID']);
    }
}




?>