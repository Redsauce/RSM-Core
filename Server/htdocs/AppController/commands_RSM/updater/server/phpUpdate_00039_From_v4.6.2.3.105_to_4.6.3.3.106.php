<?php
// Database connection startup
include "../../utilities/RSdatabase.php";
include "../../utilities/RSMitemsManagement.php";

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
$postSQLs = explode(";",file_get_contents("./phpUpdate_00039_From_v4.6.2.3.105_to_4.6.3.3.106/update_post.sql"));

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
