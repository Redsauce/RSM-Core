<?php
// Database connection startup
require_once '../../utilities/RSconfiguration.php';

$oldVersion = "5.1.2.3.123";
$newVersion = "5.1.3.3.124";

//connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
}

// Include php update files
include "./phpUpdate_From_v" . $oldVersion . "_to_v" . $newVersion . "/updateSubjectType.php";
include "./phpUpdate_From_v" . $oldVersion . "_to_v" . $newVersion . "/remove_app_properties.php";

//Launch the update php for the defined clients
$clientsToUpdate = array();
//empty to check all clients
//$clientsToUpdate[] = '1'; //Redsauce Client
echo start_update_subjects($clientsToUpdate);

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
