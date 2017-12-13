<?php
    //***************************************************
    //Description:
    //   Remove app properties and item types regarding the concepts
    //***************************************************    
    deleteAppItemType('40');
    deleteAppItemType('41');
    deleteAppItemType('43');
    deleteAppItemType('47');
    deleteAppItemType('45');
    
    function deleteAppItemType($appItemTypeID) {
        echo ("Removing App ItemType ID " . $appItemTypeID . "...");
        // Get a list of the children properties and delete them and their relationships
        $properties = $mysqli->query("SELECT RS_ID FROM rs_property_app_definitions WHERE RS_ITEM_TYPE_ID = '" . $appItemTypeID . "';");
        
        if (!$properties) {
            echo("Error executing query: " . "SELECT RS_ID FROM rs_properties_app_definitions WHERE RS_ITEM_TYPE_ID = '" . $appItemTypeID . "';\n");
            continue;
        }
        
        while ($property = $properties->fetch_assoc()) deleteAppProperty($property['RS_ID']);
        
        $result = $mysqli->query("DELETE FROM rs_item_type_app_definitions WHERE RS_ID = '".$appItemTypeID."';");
        
                
        if (!$result) {
            echo("Error executing query: " . "DELETE FROM rs_item_type_app_definitions WHERE RS_ID = '".$appItemTypeID."';\n");
            continue;
        }
        
        $result = $mysqli->query("DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = '".$appItemTypeID."';");
        
        if (!$result) {
            echo("Error executing query: " . "DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = '".$appItemTypeID."';\n");
            continue;
        }
                
        echo (" done.\n");
    }
    
    function deleteAppProperty($appPropertyID) {
        $result = $mysqli->query("DELETE FROM rs_property_app_definitions WHERE RS_ID = '".$appPropertyID."';");
        
        if (!$result) {
            echo("Error executing query: " . "DELETE FROM rs_property_app_definitions WHERE RS_ID = '".$appPropertyID."';\n");
            continue;
        }
        
        $result = $mysqli->query("DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = '".$appPropertyID."';");
        
                if (!$result) {
            echo("Error executing query: " . "DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = '".$appPropertyID."';\n");
            continue;
        }
    }


?>