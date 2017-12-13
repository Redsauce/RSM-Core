<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMdefinitions.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];
$login = base64_decode($GLOBALS['RS_POST']['login']);
$password = $GLOBALS['RS_POST']['password'];
$personID = $GLOBALS['RS_POST']['personID'];

//First of all, we need to check if the variable clientID does not have the value 0
if ($clientID > 0) {
	//We check if the user already exists
	$theQuery_userExists = "SELECT RS_USER_ID, RS_PASSWORD FROM rs_users WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
	$resultUsers = RSQuery($theQuery_userExists);
	if ($resultUsers->fetch_array() != 0) {
			// The user exists, so check if the login is valid
			$theQuery_loginAlreadyExists = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID <> ".$userID." AND RS_LOGIN ='".$login."' AND RS_CLIENT_ID = ".$clientID;
			$result = RSQuery($theQuery_loginAlreadyExists);
			if ($result->fetch_array() != 0){
				RSReturnError("USER ALREADY EXISTS", "1");
				exit;
			}

			// now we can update the user
			if ($personID == '0') {

				// get staff item type
				$staffItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID);

				// add new entry
				$personID = createEmptyItem($staffItemTypeID, $clientID);

				// update main value with the login (TODO: the main value of the items "staff" may be not the name or an identifier name... so we could add an application property called "name" to the staff itemtype and use it for saving login into the item entry)
				setPropertyValueByID(getMainPropertyID($staffItemTypeID, $clientID), $staffItemTypeID, $personID, $clientID, $login, '', $RSuserID);
			}

			if ($GLOBALS['RS_POST']['passwordChanged'] == "1" && $password != "") {
				$theQuery = "UPDATE rs_users SET RS_LOGIN = '".$login."', RS_PASSWORD = '".$password."', RS_ITEM_ID = ".$personID." WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
			} else {
				$theQuery = "UPDATE rs_users SET RS_LOGIN = '".$login."', RS_ITEM_ID = ".$personID." WHERE RS_USER_ID = ".$userID." AND RS_CLIENT_ID = ".$clientID;
			}

			if ($result = RSQuery($theQuery)) {

				$results['result'] = "OK";
				$results['login'] = $login;
				$results['personID'] = $personID;
				$results['passwordChanged'] = $GLOBALS['RS_POST']['passwordChanged'];
			} else {
				RSReturnError("ERROR WHILE UPDATING USER", "3");
			}

	} else {
		RSReturnError("ERROR WHILE UPDATING USER", "3");
	}

} else {
	RSReturnError("ERROR WHILE UPDATING USER", "3");
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
