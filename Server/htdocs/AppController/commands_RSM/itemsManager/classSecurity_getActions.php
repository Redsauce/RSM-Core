<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

isset($GLOBALS['RS_POST']['clientID']) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['userID']) ? $userID   = $GLOBALS['RS_POST']['userID'] : dieWithError(400);

if ($clientID != 0) {
    //We check if the user exists into the client
    $theQuery_userValidation = "SELECT RS_USER_ID FROM rs_users WHERE RS_USER_ID ='" . $userID . "' AND RS_CLIENT_ID=" . $clientID;

    $resultUserOK = RSquery($theQuery_userValidation);

    if ($resultUserOK->num_rows != 0) {
        $results = array();

        //The users exists, so perform the action
        $theQuery = "SELECT rs_actions.RS_NAME, rs_actions.RS_CONFIGURATION_ITEMTYPE, rs_actions.RS_DESCRIPTION, rs_actions.RS_APPLICATION_NAME, rs_actions.RS_APPLICATION_LOGO, rs_actions_clients.RS_ID, rs_actions_clients.RS_CONFIGURATION_ITEM_ID FROM rs_actions INNER JOIN (rs_actions_clients INNER JOIN rs_actions_groups ON rs_actions_clients.RS_ID=rs_actions_groups.RS_ACTION_CLIENT_ID AND rs_actions_clients.RS_CLIENT_ID=rs_actions_groups.RS_CLIENT_ID) ON rs_actions.RS_ID=rs_actions_clients.RS_ACTION_ID WHERE rs_actions_groups.RS_CLIENT_ID=" . $clientID . " AND rs_actions_groups.RS_GROUP_ID IN ( SELECT rs_users_groups.RS_GROUP_ID FROM rs_users_groups INNER JOIN rs_groups ON rs_groups.RS_GROUP_ID=rs_users_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_users_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_CLIENT_ID =" . $clientID . " AND rs_users_groups.RS_USER_ID =" . $userID . ")";

        $result = RSquery($theQuery);

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $results[$row['RS_ID']] = array();
                $results[$row['RS_ID']]['ID'] = $row['RS_ID'];
                $results[$row['RS_ID']]['action'] = $row['RS_NAME'];
                $results[$row['RS_ID']]['name'] = $row['RS_APPLICATION_NAME'];
                $results[$row['RS_ID']]['description'] = $row['RS_DESCRIPTION'];
                $results[$row['RS_ID']]['logo'] = bin2hex($row['RS_APPLICATION_LOGO']);

                $clientItemTypeID  = getClientItemTypeIDRelatedWithByName($row['RS_CONFIGURATION_ITEMTYPE'], $clientID);
                $clientName        = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'] . '.name', $row['RS_CONFIGURATION_ITEM_ID'], $clientID);
                $clientDescription = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'] . '.description', $row['RS_CONFIGURATION_ITEM_ID'], $clientID);
                $propertyID        = getClientPropertyIDRelatedWithByName($row['RS_CONFIGURATION_ITEMTYPE'] . '.logo', $clientID);
                $clientLogo        = getItemDataPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], $propertyID, $clientID);

                if ($clientName        != '') {
                    $results[$row['RS_ID']]['name'] = $clientName;
                }
                if ($clientDescription != '') {
                    $results[$row['RS_ID']]['description'] = $clientDescription;
                }
                if ($clientLogo        != '') {
                    $results[$row['RS_ID']]['logo'] = $clientLogo;
                }

                $additionalProperties = getAppItemTypeProperties(getAppItemTypeIDByName($row['RS_CONFIGURATION_ITEMTYPE']));
                foreach ($additionalProperties as $additionalProperty) {
                    $propertyID   = getClientPropertyIDRelatedWithByName($additionalProperty['propertyName'], $clientID);

                    //continue proccessing only if the property exists (app_property is related)
                    if ($propertyID != 0) {
                        $propertyType = getPropertyType($propertyID, $clientID);

                        if ($propertyType == 'image' || $propertyType == 'file') {
                            $results[$row['RS_ID']][$additionalProperty['propertyName']] = getItemDataPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], $propertyID, $clientID, $propertyType);
                        } else {
                            $results[$row['RS_ID']][$additionalProperty['propertyName']] = getItemPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], $propertyID, $clientID, $propertyType);
                        }
                    }
                }
            }
        }

        RSreturnArrayQueryResults($results);
        return;
    } else {
        $results["result"] = "NOK";
    }
} else {
    $results["result"] = "NOK";
}
RSreturnArrayResults($results);
