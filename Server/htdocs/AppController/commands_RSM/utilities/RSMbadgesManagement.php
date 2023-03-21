<?php
// Database connection startup
require_once "RSdatabase.php";

// Functions in this file related with the use of badges in RSM
// - RSbadgeExist
// - RSupdateBadgeForUser
// - generateRandomString
// - RSgetUniqueBadge
// - RSupdateAllBadgeUsers


function RSbadgeExist($RSbadge, $RSclientID = null) {
    /*
    Optionally we can restrict the action to a single clientID.
    */
    
    $query = "SELECT 'RS_BADGE'
    FROM rs_users
    WHERE RS_BADGE = '" . $RSbadge . "'";

    if ($RSclientID != null) {
        $query .= " AND RS_Client_ID = '$RSclientID'";
    }
    
    $results = RSQuery($query);
    if ($results->num_rows > 0){
        return true;
    } else {
        return false;
    }
    
}


function RSupdateBadgeForUser($userID, $clientID) {
    $uniqueBadge = RSgetUniqueBadge();
    $results = RSQuery("UPDATE rs_users SET RS_BADGE = '".$uniqueBadge."' 
                        WHERE RS_USER_ID = " . $userID . " AND 
                        RS_CLIENT_ID = " . $clientID . ";");
    return $results;
}


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


function RSgetUniqueBadge($RSclientID = null){
    /* ***************************************************************************************
    DESCRIPTION
    Create a new badge that does not exist in the list of user badges in the database.
    Optionally we can restrict the action to a single clientID.

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
        $results = RSbadgeExist($badge, $RSclientID);
        if($results == true){
            $exists = true;
        }

    } while ($exists == true);

    // If the execution reaches this point, the badge does not exist so we can return it
    return $badge;

}


function RSupdateAllBadgeUsers($RSclientID = null){
    /*
    Optionally we can restrict the action to a single clientID.
    */

    $theQuery_users = "SELECT RS_USER_ID, RS_CLIENT_ID FROM rs_users";
    
    if ($RSclientID != null) {
        $theQuery_users .= " WHERE RS_Client_ID = '$RSclientID'";
    }
    
    $resultUsers = RSQuery($theQuery_users);

    while($row=$resultUsers->fetch_assoc()){
        RSupdateBadgeForUser($row['RS_USER_ID'], $row['RS_CLIENT_ID']);
    }
}

?>