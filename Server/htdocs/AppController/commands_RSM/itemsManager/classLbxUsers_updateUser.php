<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMdefinitions.php";
require_once "../utilities/RSMbadgesManagement.php";

isset($GLOBALS['RS_POST']['password']) ? $password = $GLOBALS['RS_POST']['password'] : $password = "";
isset($GLOBALS['RS_POST']['badge'   ]) ? $badge    = $GLOBALS['RS_POST']['badge'   ] : $badge = RSgetUniqueBadge($GLOBALS['RS_POST']['clientID']);
$login    = base64_decode($GLOBALS['RS_POST']['login']);
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID   = $GLOBALS['RS_POST']['userID'  ];
$personID = $GLOBALS['RS_POST']['personID'];


// Check that the received clientID is a valid number
if ($clientID > 0) {
	
	// We check if the user already exists
	$theQuery_userExists = "SELECT RS_USER_ID, RS_PASSWORD FROM rs_users WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
	$resultUsers = RSQuery($theQuery_userExists);
	
	if ($resultUsers->fetch_array() != 0) {
			// The user exists, so check if the login is valid
			$theQuery_loginAlreadyExists = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID <> ".$userID." AND RS_LOGIN ='".$login."' AND RS_CLIENT_ID = ".$clientID;
			$result = RSQuery($theQuery_loginAlreadyExists);
			
			if ($result->fetch_array() != 0) {
				RSReturnError("USER ALREADY EXISTS", "1");
			}

			// Now we can update the user
			if ($personID == '0') {

				// Get staff item type
				$staffItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID);

				// Add new entry
				$personID = createEmptyItem($staffItemTypeID, $clientID);

				// Update main value with the login (TODO: the main value of the items "staff" may be not the name or an identifier name... so we could add an application property called "name" to the staff itemtype and use it for saving login into the item entry)
				setPropertyValueByID(getMainPropertyID($staffItemTypeID, $clientID), $staffItemTypeID, $personID, $clientID, $login, '', $RSuserID);

			}

			// Check if password or badge has changed
			$passwordChanged = $GLOBALS['RS_POST']['passwordChanged'] == "1" && $password != "";
			$badgeChanged    = $GLOBALS['RS_POST']['badgeChanged'   ] == "1";

			// Build the query base
			$theQuery = "UPDATE rs_users SET RS_LOGIN = '" . $login . "'";
			
			// Add password change if needed
			if ($passwordChanged) {
				$theQuery .= ", RS_PASSWORD = '" . $password . "'";
			}
						
			$theQuery .= ", RS_ITEM_ID = " . $personID;
			
			// Add badge change if needed
			if ($badgeChanged) {
				$theQuery .= ", RS_BADGE = '" . $badge . "'";
			}

			$theQuery .= " WHERE RS_USER_ID = " . $userID . " AND RS_CLIENT_ID = " . $clientID;

			// Execute the query
			if ($result = RSQuery($theQuery)) {
				$results['result'         ] = "OK";
				$results['login'          ] = $login;
				$results['personID'       ] = $personID;
				$results['badge'          ] = $badge;
				$results['passwordChanged'] = $passwordChanged;
				$results['badgeChanged'   ] = $badgeChanged;

			} else {
				RSReturnError("QUERY ERROR UPDATING USER", "2");
			}

	} else {
		RSReturnError("QUERY ERROR UPDATING USER. USER NOT FOUND.", "3");
	}

} else {
	RSReturnError("ERROR UPDATING USER. INVALID CLIENT NUMBER.", "4");
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
