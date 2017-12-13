<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";

$currentVersion = "4.9.0.3.114";
$newVersion = "5.0.0.3.115";

//connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
}

$postSQLs = explode(";",file_get_contents("./phpUpdate_From_v" . $currentVersion . "_to_v" . $newVersion . "/update_post.sql"));

//begin transaction
$result = $mysqli->begin_transaction();
if (!$result) die("[ERROR]: " . $mysqli->error);

foreach($postSQLs as $postSQL){
	if(trim($postSQL)!=""){
		echo ("Executing query: " . trim($postSQL) . "\n\n");

		if(!$mysqli->query(trim($postSQL))){
			//rollback transaction and exit
			echo ("[ERROR]: Update cancelled due to error on this query: " . $postSQL . "\n\n");

			if (!$mysqli->rollback()) {
				echo("[WARNING]: Rollback procedure failed");
			} else {
				echo("Rollback procedure executed successfully");
			}

			exit;
		}
	}
}

//commit transaction
$mysqli->commit();

echo ("[SUCCESS]: Database successfully updated from v" . $currentVersion . " to v" . $newVersion . "\n\n");
?>
