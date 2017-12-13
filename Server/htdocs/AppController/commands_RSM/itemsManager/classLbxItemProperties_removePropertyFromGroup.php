<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMusersManagement.php";

isset($GLOBALS["RS_POST"]["clientID"  ]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"  ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["groupID"   ]) ? $groupID    = $GLOBALS["RS_POST"]["groupID"   ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyID"]) ? $propertyID = $GLOBALS["RS_POST"]["propertyID"] : dieWithError(400);

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
