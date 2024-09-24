<?php
// Database connection startup
include_once "../../utilities/RSconfiguration.php";

$oldVersion = "6.7.2.3.161";
$newVersion = "6.8.0.3.162";

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
echo ("[SUCCESS]: Database successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
?>