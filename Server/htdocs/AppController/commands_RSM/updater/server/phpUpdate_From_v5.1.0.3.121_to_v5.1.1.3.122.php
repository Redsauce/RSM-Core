<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";

$oldVersion = "5.1.0.3.121";
$newVersion     = "5.1.1.3.122";

//connect to the database using the above settings	
if(!$bd = mysql_connect($RShost, $RSuser,$RSpassword)){
	echo ("CANNOT CONNECT TO DATABASE SERVER");
} elseif (!$sel = mysql_select_db($RSdatabase)) {
	echo ("DATABASE DOES NOT EXIST IN SERVER");
}

$postSQLs = explode(";",file_get_contents("./phpUpdate_From_v" . $oldVersion . "_to_v" . $newVersion . "/update_post.sql"));

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

echo ("[SUCCESS]: Database successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
?>
