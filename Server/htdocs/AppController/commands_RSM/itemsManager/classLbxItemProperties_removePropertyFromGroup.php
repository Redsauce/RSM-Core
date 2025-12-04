<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMusersManagement.php";

isset($GLOBALS[$cstRS_POST][$cstClientID])   ? $clientID   = $GLOBALS[$cstRS_POST][$cstClientID]   : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstGroupID])    ? $groupID    = $GLOBALS[$cstRS_POST][$cstGroupID]    : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstPropertyID]) ? $propertyID = $GLOBALS[$cstRS_POST][$cstPropertyID] : dieWithError(400);

$results["result"] = "NOK";

if ($clientID != 0) {
		// We check if the property and the group exists for the client
		$theQuery_propertyValidation = "SELECT RS_PROPERTY_ID FROM rs_item_properties WHERE RS_PROPERTY_ID =" . $propertyID . " AND RS_CLIENT_ID=" . $clientID;
		$theQuery_groupValidation    = "SELECT RS_GROUP_ID    FROM rs_groups          WHERE RS_GROUP_ID ="    . $groupID    . " AND RS_CLIENT_ID=" . $clientID;

		$resultpropertyOK = RSQuery($theQuery_propertyValidation);
		$resultGroupOK    = RSQuery($theQuery_groupValidation   );

		if (($resultpropertyOK->num_rows != 0) && ($resultGroupOK->num_rows != 0)) {
				//The property exists, so perform the action
				$results["result"] = removePropertyFromGroup($propertyID, $groupID,$clientID);
		}
	}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
