<?php
//***************************************************
//Description:
//	 Update all projects. switch client property to multiidentifier and move property for existing items
//***************************************************

/* LAUNCH THE UPDATE MAIN PROCESS */
function start_update_stock_modules($clientsToUpdate) {

    global $definitions;

    if (count($clientsToUpdate) == 0) {
        print("No specific clients defined, processing all clients: �\n");
        // get all clients
        $theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");

        while ($client = $theQuery->fetch_assoc()) {
            $clientsToUpdate[] = $client['RS_ID'];
        }
    }

    print("Selected clients to update: �\n");
    print_r($clientsToUpdate);

    //Update the defined clients
    for ($cliNum = 0; $cliNum < count($clientsToUpdate); $cliNum++) {

        //Get the clientID
        $clientID = $clientsToUpdate[$cliNum];

        print("Starting process for client" . $clientID . "�\n");

        //get stock client_actions
        $theQuery = "SELECT rs_actions_clients.RS_ID, rs_actions_clients.RS_CONFIGURATION_ITEM_ID FROM rs_actions_clients INNER JOIN rs_actions ON rs_actions_clients.RS_ACTION_ID=rs_actions.RS_ID WHERE rs_actions.RS_NAME='rsm.mainpanel.stockitems.access' AND rs_actions_clients.RS_CLIENT_ID = " . $clientID;
        $stockResults = RSquery($theQuery);

        if ($stockResults->num_rows > 0) {
            //check itemtype exists
            $itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['configurationGenericModule'], $clientID);
            if ($itemTypeID == 0) {
                //create itemtype
                $itemTypeID = getNextIdentification('rs_item_types', 'RS_ITEMTYPE_ID', $clientID);
                $theQuery = "INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ORDER) VALUES (" . $itemTypeID . ",0,'" . $clientID . "', 'configurationGenericModule', " . getGenericNext('rs_item_types', 'RS_ORDER', array("RS_CLIENT_ID" => $clientID)) . ")";
                $result = RSquery($theQuery);
                //relate itemtype
                createItemTypeRelationship($itemTypeID, getAppItemTypeIDByName($definitions['configurationGenericModule']), $clientID);
            }
            //check category exists
            $categories = getClientItemTypeCategories($itemTypeID, $clientID);
            if (count($categories) > 0) {
                $categoryID = $categories[0]['id'];
            } else {
                //create category
                $categoryID = getNextIdentification('rs_categories', 'RS_CATEGORY_ID', $clientID);
                $theQuery = "INSERT INTO rs_categories (RS_CATEGORY_ID, RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_NAME, RS_ORDER) VALUES (" . $categoryID . ",'" . $clientID . "','" . $itemTypeID . "', 'main', " . getGenericNext('rs_categories', 'RS_ORDER', array("RS_CLIENT_ID" => $clientID)) . ")";
                $result = RSquery($theQuery);
            }

            $namePropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleName'], $clientID);
            if ($namePropertyID == 0) {
                //create property
                $namePropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
                $theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES (' . $namePropertyID . ',' . $categoryID . ',' . $clientID . ',"name","text","",' . getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)) . ',"",0,0,0)';
                $result = RSquery($theQuery);
                //relate property
                $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $namePropertyID . "', '" . $clientID . "', '" . getAppPropertyIDByName($definitions['configurationGenericModuleName']) . "', NOW())";
                $result = RSquery($theQuery);
            }

            $descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleDescription'], $clientID);
            if ($descriptionPropertyID == 0) {
                //create property
                $descriptionPropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
                $theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES (' . $descriptionPropertyID . ',' . $categoryID . ',' . $clientID . ',"description","text","",' . getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)) . ',"",0,0,0)';
                $result = RSquery($theQuery);
                //relate property
                $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $descriptionPropertyID . "', '" . $clientID . "', '" . getAppPropertyIDByName($definitions['configurationGenericModuleDescription']) . "', NOW())";
                $result = RSquery($theQuery);
            }

            $logoPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleLogo'], $clientID);
            if ($logoPropertyID == 0) {
                //create property
                $logoPropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
                $theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES (' . $logoPropertyID . ',' . $categoryID . ',' . $clientID . ',"logo","image","",' . getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)) . ',"",0,0,0)';
                $result = RSquery($theQuery);
                //relate property
                $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $logoPropertyID . "', '" . $clientID . "', '" . getAppPropertyIDByName($definitions['configurationGenericModuleLogo']) . "', NOW())";
                $result = RSquery($theQuery);
            }

            $basePropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleBase'], $clientID);
            if ($basePropertyID == 0) {
                //create property
                $basePropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
                $theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES (' . $basePropertyID . ',' . $categoryID . ',' . $clientID . ',"baseItemTypes","text","",' . getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)) . ',"",0,0,0)';
                $result = RSquery($theQuery);
                //relate property
                $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $basePropertyID . "', '" . $clientID . "', '" . getAppPropertyIDByName($definitions['configurationGenericModuleBase']) . "', NOW())";
                $result = RSquery($theQuery);
            }

            $allowedPropertyID = getClientPropertyID_RelatedWith_byName($definitions['configurationGenericModuleAllowed'], $clientID);
            if ($allowedPropertyID == 0) {
                //create property
                $allowedPropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $clientID);
                $theQuery = 'INSERT INTO rs_item_properties (RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED) VALUES (' . $allowedPropertyID . ',' . $categoryID . ',' . $clientID . ',"allowedItemTypes","text","",' . getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID, 'RS_CATEGORY_ID' => $categoryID)) . ',"",0,0,0)';
                $result = RSquery($theQuery);
                //relate property
                $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $allowedPropertyID . "', '" . $clientID . "', '" . getAppPropertyIDByName($definitions['configurationGenericModuleAllowed']) . "', NOW())";
                $result = RSquery($theQuery);
            }

            $oldItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['configurationModuleGeneric'], $clientID);

            while ($row = $stockResults->fetch_assoc()) {
                //create item and properties
                $propertiesValues = array();
                $propertiesValues[] = array('ID' => $namePropertyID, 'value' => getItemPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], getClientPropertyID_RelatedWith_byName($definitions['configurationModuleGenericName'], $clientID), $clientID));
                $propertiesValues[] = array('ID' => $descriptionPropertyID, 'value' => getItemPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], getClientPropertyID_RelatedWith_byName($definitions['configurationModuleGenericDescription'], $clientID), $clientID));
                $propertiesValues[] = array('ID' => $logoPropertyID, 'value' => getItemPropertyValue($row['RS_CONFIGURATION_ITEM_ID'], getClientPropertyID_RelatedWith_byName($definitions['configurationModuleGenericLogo'], $clientID), $clientID));
                $propertiesValues[] = array('ID' => $basePropertyID, 'value' => 'stockItemGroup');
                $propertiesValues[] = array('ID' => $allowedPropertyID, 'value' => 'stockItemGroup,stockItem');
                $configurationItemID = createItem($itemTypeID, $clientID, $propertiesValues);

                //delete old configuration item
                deleteItem($oldItemTypeID, $row['RS_CONFIGURATION_ITEM_ID'], $clientID);

                //update action and configuration item
                $theQuery = "UPDATE rs_actions_clients SET RS_ACTION_ID = (SELECT RS_ID FROM rs_actions WHERE RS_NAME='rsm.mainpanel.generic.access'), RS_CONFIGURATION_ITEM_ID = " . $configurationItemID . " WHERE RS_CLIENT_ID = " . $clientID . " AND RS_ID = " . $row['RS_ID'];
                $result = RSquery($theQuery);
            }
        }

        print("Client with ID" . $clientID . "Finished!�\n");
    }

    //RETURN OK RESULT
    return "OK";

}
?>