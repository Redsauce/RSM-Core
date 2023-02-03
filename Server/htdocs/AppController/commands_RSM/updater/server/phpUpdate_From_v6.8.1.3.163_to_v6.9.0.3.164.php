<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";

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
include "../../utilities/RSMbadgesManagement.php";
//RSupdateBadgeForUser(74, 28);
RSupdateAllBadgeUsers();

// Closes the modification of the user table
$mysqli->query("ALTER TABLE rs_users CHANGE `RS_BADGE` `RS_BADGE` CHAR(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;");
$mysqli->query("ALTER TABLE rs_users ADD UNIQUE(RS_BADGE);");

echo ("[SUCCESS]: Database successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
?>