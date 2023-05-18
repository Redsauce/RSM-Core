<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";
include "../../utilities/RSMbadgesManagement.php";

$oldVersion = "6.8.1.3.163";
$newVersion = "6.9.0.3.164";

//connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}

$postSQLs = explode(";",file_get_contents("./phpUpdate_From_v" . $oldVersion . "_to_v" . $newVersion . "/update_post.sql"));

//begin transaction
$result = $mysqli->query("BEGIN");
if (!$result) die("[ERROR]: " . $mysqli->error);

foreach($postSQLs as $postSQL){
    if(trim($postSQL)!=""){
        echo ("Executing query: " . trim($postSQL) . "\n\n");

        if(!$mysqli->query(trim($postSQL))){
            //rollback transaction and exit
            echo ("[ERROR]: Update cancelled due to error: " . $mysqli->error . ". On this query: " . $postSQL . "\n\n");

            if (!$mysqli->query("ROLLBACK")) {
                echo("[WARNING]: Rollback procedure failed");
            } else {
                echo("Rollback procedure executed successfully");
            }

            exit;
        }
    }
}

//commit transaction
$mysqli->query("COMMIT");

// Assign a badget for each user
function RSupdateAllBadgeUsers($RSclientID = null){
    $theQuery_users = "SELECT RS_USER_ID, RS_CLIENT_ID FROM rs_users";
    
    if ($RSclientID != null) {
        $theQuery_users .= " WHERE RS_CLIENT_ID = '$RSclientID'";
    }
    
    $resultUsers = RSQuery($theQuery_users);
    
    while ($row=$resultUsers->fetch_assoc()){
        RSupdateBadgeForUser($row['RS_USER_ID'], $row['RS_CLIENT_ID']);
    }
}

echo("Creating badges for all users... \n\n");
RSupdateAllBadgeUsers();

// Closes the modification of the user table
echo ("Modifying the user table to make badges unique for each customer...\n\n");
$mysqli->query("ALTER TABLE rs_users CHANGE `RS_BADGE` `RS_BADGE` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
$mysqli->query("ALTER TABLE rs_users ADD CONSTRAINT RS_BADGE_UNIQUE UNIQUE KEY (RS_BADGE, RS_CLIENT_ID)");

echo ("[SUCCESS]: Database successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");

?>
