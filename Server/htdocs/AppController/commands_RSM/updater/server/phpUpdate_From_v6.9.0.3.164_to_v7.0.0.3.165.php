<?php
// Database connection startup
include_once "../../utilities/RSconfiguration.php";

$oldVersion = "6.9.0.3.164";
$newVersion = "7.0.0.3.165";

// Function to execute update on a specific database connection
function executeUpdate($mysqli, $dbName, $postSQLs) {
	echo ("\n========================================\n");
	echo ("Updating database: " . $dbName . "\n");
	echo ("========================================\n\n");
	
	//begin transaction
	$result = $mysqli->query("BEGIN");
	if (!$result) {
		echo ("[ERROR] on " . $dbName . ": " . $mysqli->error . "\n\n");
		return false;
	}

	foreach($postSQLs as $postSQL){
		if(trim($postSQL)!=""){
			echo ("Executing query: " . trim($postSQL) . "\n\n");

			if(!$mysqli->query(trim($postSQL))){
				//rollback transaction and exit
				echo ("[ERROR] on " . $dbName . ": Update cancelled due to error: " . $mysqli->error . ". On this query: " . $postSQL . "\n\n");

				if (!$mysqli->query("ROLLBACK")) {
					echo("[WARNING]: Rollback procedure failed");
				} else {
					echo("Rollback procedure executed successfully");
				}

				return false;
			}
		}
	}

	//commit transaction
	if (!$mysqli->query("COMMIT")) {
		echo ("[ERROR] on " . $dbName . ": Failed to commit transaction: " . $mysqli->error . "\n\n");
		return false;
	}
	
	echo ("[SUCCESS]: Database " . $dbName . " successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
	return true;
}

//connect to the main database using the above settings
$mainMysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mainMysqli->connect_errno) {
	die('Connect Error: ' . $mainMysqli->connect_error);
}

$postSQLs = explode(";",file_get_contents("./phpUpdate_From_v" . $oldVersion . "_to_v" . $newVersion . "/update_post.sql"));

// First, update the main database
$successCount = 0;
$failureCount = 0;

if (executeUpdate($mainMysqli, $RSdatabase, $postSQLs)) {
	$successCount++;
} else {
	$failureCount++;
}

// Now, get all clients with custom database credentials
$query = "SELECT RS_ID, RS_DB_NAME, RS_DB_USER, RS_DB_PASSWORD FROM rs_clients WHERE (RS_DB_NAME IS NOT NULL AND RS_DB_NAME != '') OR (RS_DB_USER IS NOT NULL AND RS_DB_USER != '')";
$result = $mainMysqli->query($query);

if ($result && $result->num_rows > 0) {
	echo ("\n\n========================================\n");
	echo ("Found " . $result->num_rows . " client(s) with custom database configuration\n");
	echo ("========================================\n\n");
	
	while ($row = $result->fetch_assoc()) {
		$clientID = $row['RS_ID'];
		$clientDbName = $row['RS_DB_NAME'];
		$clientDbUser = $row['RS_DB_USER'];
		$clientDbPassword = $row['RS_DB_PASSWORD'];
		
		// Use default values if client-specific ones are not set
		$targetDb   = (!empty($clientDbName) ? $clientDbName : $RSdatabase);
		$targetUser = (!empty($clientDbUser) ? $clientDbUser : $RSuser);
		$targetPass = (!empty($clientDbPassword) ? $clientDbPassword : $RSpassword);
		
		echo ("Attempting to connect to client ID " . $clientID . " database: " . $targetDb . "\n");
		
		try {
			$clientMysqli = new mysqli($RShost, $targetUser, $targetPass, $targetDb);
			
			if ($clientMysqli->connect_errno) {
				echo ("[ERROR]: Could not connect to client database " . $targetDb . ": " . $clientMysqli->connect_error . "\n\n");
				$failureCount++;
				continue;
			}
			
			// Set charset
			if (!@$clientMysqli->set_charset('utf8mb4')) {
				@$clientMysqli->set_charset('utf8');
			}
			
			// Execute update on client database
			if (executeUpdate($clientMysqli, $targetDb . " (Client ID: " . $clientID . ")", $postSQLs)) {
				$successCount++;
			} else {
				$failureCount++;
			}
			
			$clientMysqli->close();
		} catch (Exception $e) {
			echo ("[ERROR]: Exception connecting to client database " . $targetDb . ": " . $e->getMessage() . "\n\n");
			$failureCount++;
		}
	}
}

$mainMysqli->close();

// Final summary
echo ("\n\n========================================\n");
echo ("UPDATE SUMMARY\n");
echo ("========================================\n");
echo ("Successful updates: " . $successCount . "\n");
echo ("Failed updates: " . $failureCount . "\n");
echo ("========================================\n\n");

if ($failureCount > 0) {
	die("[ERROR]: Some updates failed. Please review the errors above.\n\n");
}

echo ("[SUCCESS]: All databases successfully updated from v" . $oldVersion . " to v" . $newVersion . "\n\n");
?>
