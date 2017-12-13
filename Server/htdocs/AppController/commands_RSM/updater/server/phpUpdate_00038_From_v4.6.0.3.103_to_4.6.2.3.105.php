<?php
// Database connection startup
include "../../utilities/RSdatabase.php";
include "../../utilities/RSMitemsManagement.php";

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
include "./phpUpdate_00038_From_v4.6.0.3.103_to_4.6.2.3.105/updateStockModulesForClient.php";
include "./phpUpdate_00038_From_v4.6.0.3.103_to_4.6.2.3.105/createStoreModulesForClient.php";
$preSQLs = explode(";",file_get_contents("./phpUpdate_00038_From_v4.6.0.3.103_to_4.6.2.3.105/update_prev.sql"));
$postSQLs = explode(";",file_get_contents("./phpUpdate_00038_From_v4.6.0.3.103_to_4.6.2.3.105/update_post.sql"));

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
//$clientsToUpdate[] = '1'; //Redsauce Client
echo start_update_stock_modules($clientsToUpdate);

//Launch the update php for the defined clients
$clientsToUpdate = array();
//empty to check all clients
$clientsToUpdate[] = '1'; //Redsauce Client
echo start_create_store_modules($clientsToUpdate);

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
