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
    $allowedCharacters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $allowedCharacters[random_int(0, strlen($allowedCharacters) - 1)];
    }

    return $randomString;
}


function RSgetUniqueBadge($RSclientID = null){
    /* Returns a badge that does not exist in the database.
    Optionally we can restrict the action to a single clientID. */

    $exists = false;

    do {
        $badge = md5(generateRandomString(256));
        
        $results = RSbadgeExist($badge, $RSclientID);
        if($results == true){
            $exists = true;
        }

    } while ($exists == true);

    // If the execution reaches this point, the badge does not exist so we can return it
    return $badge;

}


function RSupdateAllBadgeUsers($RSclientID = null){
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