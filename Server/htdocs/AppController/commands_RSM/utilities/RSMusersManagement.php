<?php
function removeUserFromGroup($userID, $clientID, $groupID) {
	//First of all, we need to check if the variables groupID and userID does not have the value 0

	if (($groupID < 1) || ($userID < 1) || ($clientID < 1))
		return ("NOK");

	//We check if the user is already into the group
	$theQuery_userAlreadyNotinGroup = "SELECT RS_GROUP_ID FROM rs_users_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_USER_ID=" . $userID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSQuery($theQuery_userAlreadyNotinGroup);

	if ($result->fetch_array() == 0) {

		return ("OK");
	} else {
		$theQuery = "DELETE FROM rs_users_groups WHERE (RS_GROUP_ID=" . $groupID . " AND RS_USER_ID=" . $userID . " AND RS_CLIENT_ID=" . $clientID . ")";

		if ($result = RSQuery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}

	}
}

function addUserToGroup($userID, $clientID, $groupID) {
	if (($groupID < 1) || ($userID < 1) || ($clientID < 1))
		return ("NOK");

	//We check if the user is already into the group
	$theQuery_userAlreadyinGroup = "SELECT RS_GROUP_ID FROM rs_users_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_USER_ID=" . $userID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSQuery($theQuery_userAlreadyinGroup);

	if ($result->fetch_array() != 0) {
		return ("OK");
	} else {
		$theQuery = "INSERT INTO rs_users_groups (RS_GROUP_ID,RS_USER_ID,RS_CLIENT_ID) VALUES ('" . $groupID . "', '" . $userID . "', '" . $clientID . "')";

		if ($result = RSQuery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}
	}
}

function addPropertyToGroup($propertyID, $groupID, $clientID) {
	if (($groupID < 1) || ($propertyID < 1) || ($clientID < 1))
		return ("NOK");

	//We check if the property is already into the group
	$theQuery_propertyAlreadyinGroup = "SELECT RS_GROUP_ID FROM rs_properties_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_PROPERTY_ID=" . $propertyID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSQuery($theQuery_propertyAlreadyinGroup);

	if ($result->fetch_array() != 0) {
		return ("OK");
	} else {
		$theQuery = "INSERT INTO rs_properties_groups (RS_GROUP_ID,RS_PROPERTY_ID,RS_CLIENT_ID) VALUES (" . $groupID . ", " . $propertyID . ", " . $clientID . ")";

		if ($result = RSQuery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}
	}
}

function removePropertyFromGroup($propertyID, $groupID, $clientID) {
	//First of all, we need to check if the variables groupID and propertyID does not have the value 0

	if (($groupID < 1) || ($propertyID < 1) || ($clientID < 1))
		return ("NOK");

	//We check if the property is already into the group
	$theQuery_propertyAlreadyNotinGroup = "SELECT RS_GROUP_ID FROM rs_properties_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_PROPERTY_ID=" . $propertyID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSQuery($theQuery_propertyAlreadyNotinGroup);

	if ($result->fetch_array() == 0) {

		return ("OK");
	} else {
		$theQuery = "DELETE FROM rs_properties_groups WHERE (RS_GROUP_ID=" . $groupID . " AND RS_PROPERTY_ID=" . $propertyID . " AND RS_CLIENT_ID=" . $clientID . ")";

		if ($result = RSQuery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}

	}
}

function addActionToGroup($actionClientID, $groupID, $clientID) {

	if (($actionClientID < 1) || ($groupID < 1) || ($clientID < 1)) return ("NOK");

	//We check if the action is already into the group
	$theQuery_actionAlreadyinGroup = "SELECT RS_GROUP_ID FROM rs_actions_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_ACTION_CLIENT_ID=" . $actionClientID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSquery($theQuery_actionAlreadyinGroup);

	if ($result->fetch_array() != 0) {

		return ("OK");

	} else {
		$theQuery = "INSERT INTO rs_actions_groups (RS_GROUP_ID,RS_ACTION_CLIENT_ID,RS_CLIENT_ID) VALUES (" . $groupID . ", " . $actionClientID . ", " . $clientID . ")";

		if ($result = RSquery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}
	}
}

function removeActionFromGroup($actionClientID, $groupID, $clientID) {
	//First of all, we need to check if the variables groupID and actionID does not have the value 0

	if (($groupID < 1) || ($actionClientID < 1) || ($clientID < 1))
		return ("NOK");

	//We check if the action is already into the group
	$theQuery_actionAlreadyNotinGroup = "SELECT RS_GROUP_ID FROM rs_actions_groups WHERE RS_GROUP_ID=" . $groupID . " AND RS_ACTION_CLIENT_ID=" . $actionClientID . " AND RS_CLIENT_ID=" . $clientID;

	$result = RSquery($theQuery_actionAlreadyNotinGroup);

	if ($result->fetch_array() == 0) {
		return ("OK");

	} else {
		$theQuery = "DELETE FROM rs_actions_groups WHERE (RS_GROUP_ID=" . $groupID . " AND RS_ACTION_CLIENT_ID=" . $actionClientID . " AND RS_CLIENT_ID=" . $clientID . ")";

		if ($result = RSQuery($theQuery)) {
			return ("OK");
		} else {
			return ("NOK");
		}
	}
}

function getUserActions($userID, $clientID) {

	if (($userID < 1) || ($clientID < 1)) return ("NOK");

	$theQuery = "SELECT DISTINCT rs_actions_groups.RS_ACTION_CLIENT_ID AS actionID FROM rs_actions_groups WHERE rs_actions_groups.RS_GROUP_ID IN ( SELECT rs_users_groups.RS_GROUP_ID FROM rs_users_groups INNER JOIN rs_groups ON rs_users_groups.RS_GROUP_ID=rs_groups.RS_GROUP_ID AND rs_users_groups.RS_CLIENT_ID=rs_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_USER_ID =" . $userID . " AND rs_users_groups.RS_CLIENT_ID =" . $clientID . ") AND rs_actions_groups.RS_CLIENT_ID =" . $clientID;

	$results = RSquery($theQuery);

	return ($results);
}

function getItemPropertyLists($clientID) {
	if ($clientID < 1) return ("NOK");

	$theQuery = "SELECT rs_properties_lists.`RS_PROPERTY_ID`,rs_properties_lists.`RS_MULTIVALUES`,rs_lists.`RS_LIST_ID`,rs_lists.`RS_NAME`,rs_property_values.`RS_VALUE` FROM `rs_property_values` INNER JOIN (rs_lists INNER JOIN rs_properties_lists ON rs_lists.RS_LIST_ID=rs_properties_lists.RS_LIST_ID AND rs_lists.RS_CLIENT_ID=rs_properties_lists.RS_CLIENT_ID) ON rs_lists.RS_LIST_ID=rs_property_values.RS_LIST_ID AND rs_lists.RS_CLIENT_ID=rs_property_values.RS_CLIENT_ID WHERE rs_properties_lists.`RS_CLIENT_ID`=" . $clientID . " ORDER BY rs_properties_lists.`RS_PROPERTY_ID`,rs_property_values.`RS_ORDER`";

	$results = RSQuery($theQuery);
	return ($results);

}
?>
