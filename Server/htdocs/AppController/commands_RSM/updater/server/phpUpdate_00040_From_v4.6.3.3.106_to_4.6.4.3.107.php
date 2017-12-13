<?php
// Database connection startup
include "../../utilities/RSdatabase.php";
include "../../utilities/RSMitemsManagement.php";

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
include "./phpUpdate_00040_From_v4.6.3.3.106_to_4.6.4.3.107/updateStoreModulesForClient.php";
include "./phpUpdate_00040_From_v4.6.3.3.106_to_4.6.4.3.107/updateImagePropertiesForClient.php";
$preSQLs = explode(";",file_get_contents("./phpUpdate_00040_From_v4.6.3.3.106_to_4.6.4.3.107/update_prev.sql"));
$postSQLs = explode(";",file_get_contents("./phpUpdate_00040_From_v4.6.3.3.106_to_4.6.4.3.107/update_post.sql"));

//begin transaction
$mysqli->begin_transaction();

foreach($preSQLs as $preSQL){
	if(trim($preSQL)!=""){
		if(!RSquery(trim($preSQL))){
			//rollback transaction and exit
			echo "QUERY ERROR, UPDATE CANCELLED. query: ".$preSQL;
			$mysqli->rollback();
			exit;
		}
	}
}

//Launch the update php for the defined clients
$clientsToUpdate = array();
//empty to check all clients
$clientsToUpdate[] = '1'; //Redsauce Client
echo start_update_store_modules($clientsToUpdate);

//Launch the update php for the defined clients
$clientsToUpdate = array();
//empty to check all clients
//$clientsToUpdate[] = '1'; //Redsauce Client
echo start_update_image_properties($clientsToUpdate);

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
