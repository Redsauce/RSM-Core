<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";

//connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
}

$postSQLs = explode(";",file_get_contents("./phpUpdate_From_v4.7.0.3.108_to_4.7.1.3.109/update_post.sql"));

//begin transaction
$mysqli->begin_transaction();

foreach($postSQLs as $postSQL){
	if(trim($postSQL)!=""){
		if(!RSquery(trim($postSQL))){
			//rollback transaction and exit
			echo "QUERY ERROR, UPDATE CANCELLED. query: ".$postSQL;
			$mysqli->rollback();
			exit;
		}
	}
}

//commit transaction
$mysqli->commit();
?>
