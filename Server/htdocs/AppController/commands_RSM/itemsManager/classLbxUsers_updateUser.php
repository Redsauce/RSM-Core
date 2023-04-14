<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMdefinitions.php";
require_once "../utilities/RSMbadgesManagement.php";

$login    = base64_decode($GLOBALS['RS_POST']['login']);
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID   = $GLOBALS['RS_POST']['userID'  ];
$password = $GLOBALS['RS_POST']['password'];
$personID = $GLOBALS['RS_POST']['personID'];
$badge    = $GLOBALS['RS_POST']['badge'   ];

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
				exit;
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

			if ($GLOBALS['RS_POST']['passwordChanged'] == "1" && isset($password)) {
				// The password is properly defined
				$theQuery = "UPDATE rs_users SET RS_LOGIN = '".$login."', RS_PASSWORD = '".$password."', RS_ITEM_ID = ".$personID." WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
			} else {
				$theQuery = "UPDATE rs_users SET RS_LOGIN = '".$login."', RS_ITEM_ID = ".$personID." WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
			}

			if ($GLOBALS['RS_POST']['badgeChanged'] == "1") {

				// Ask the database for badges like the new one, for this client
				$badgeExists = RSbadgeExist($badge, $clientID);
				
				// Check if we found a badge like ours in the database
				if ($badgeExists == true) {
					RSReturnError("ERROR UPDATING USER. BADGE ALREADY EXISTS.", "2");
					exit;
				}

				$theBadgeQuery = "UPDATE rs_users SET RS_BADGE = '".$badge."' WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;

				if ($badgeResult = RSQuery($theBadgeQuery)) {
					$results['result'      ] = "OK";
					$results['login'       ] = $login;
					$results['personID'    ] = $personID;
					$results['badge'       ] = $badge;
					$results['badgeChanged'] = $GLOBALS['RS_POST']['badgeChanged'];
				} else {
					RSReturnError("QUERY ERROR UPDATING USER", "3");
				}
			}
			
			if ($result = RSQuery($theQuery)) {
				$results['result'         ] = "OK";
				$results['login'          ] = $login;
				$results['personID'       ] = $personID;
				$results['badge'          ] = $badge;
				$results['passwordChanged'] = $GLOBALS['RS_POST']['passwordChanged'];
			} else {
				RSReturnError("QUERY ERROR UPDATING USER", "4");
			}


	} else {
		RSReturnError("QUERY ERROR UPDATING USER", "5");
	}

} else {
	RSReturnError("ERROR UPDATING USER. INVALID CLIENT NUMBER.", "6");
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
