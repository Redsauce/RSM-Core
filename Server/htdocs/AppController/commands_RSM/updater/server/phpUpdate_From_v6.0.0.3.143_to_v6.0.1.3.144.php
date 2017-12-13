<?php
// Database connection startup
include "../../utilities/RSconfiguration.php";

$oldVersion = "6.0.0.3.143";
$newVersion = "6.0.1.3.144";

//connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
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

// Now update the max IDs column inside the item types tables
echo("SELECT RS_CLIENT_ID, RS_ITEMTYPE_ID FROM rs_item_types\n");
$itemTypesQuery = $mysqli->query("SELECT RS_CLIENT_ID, RS_ITEMTYPE_ID FROM rs_item_types");

while($row = $itemTypesQuery->fetch_assoc()) {
	echo("SELECT MAX(RS_ITEM_ID) as MAX FROM rs_items WHERE RS_CLIENT_ID = " . $row['RS_CLIENT_ID'] . " AND RS_ITEMTYPE_ID = " . $row['RS_ITEMTYPE_ID'] . "\n");

	$countQuery = $mysqli->query("SELECT MAX(RS_ITEM_ID) as MAX FROM rs_items WHERE RS_CLIENT_ID = " . $row['RS_CLIENT_ID'] . " AND RS_ITEMTYPE_ID = " . $row['RS_ITEMTYPE_ID']);
	$count = $countQuery->fetch_assoc();

	if ($count['MAX'] > 0) {
		echo ("Setting MAX ID of item type " . $row['RS_ITEMTYPE_ID'] . " of client " . $row['RS_CLIENT_ID'] . " to " . $count['MAX'] . "... ");
		$mysqli->query("UPDATE rs_item_types SET RS_LAST_ITEM_ID = " . $count['MAX'] . " WHERE RS_ITEMTYPE_ID = " . $row['RS_ITEMTYPE_ID'] . " AND RS_CLIENT_ID = " . $row['RS_CLIENT_ID']);
		echo ("done.\n");
	} else {
			echo("Skipped item ID " . $row['RS_ITEMTYPE_ID'] . " because there are no items of this kind in client ID " . $row['RS_CLIENT_ID'] . "\n");
	}
}

//commit transaction
$mysqli->commit();
echo ("[SUCCESS]: Database successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
?>
