<?php

class ClientProperty {
    public Database $db;
    
    function __construct($db) {
        $this->db = $db;
    }

    // Return the ID of the item type of the client's property passed
    function getClientPropertyItemType($clientPropertyID, $clientID) {
        $clientPropertyCategory = $this->getClientPropertyCategory($clientPropertyID, $clientID);
        $result =  $this->db->getClientCategoryItemType($clientPropertyCategory, $clientID);
        return $result;
    }

    function getClientPropertyCategory($clientPropertyID, $clientID) {
        $clientPropertyID = ParsePID($clientPropertyID, $clientID);
        $result =  $this->db->getClientPropertyCategory($clientPropertyID, $clientID);
        return $result;
    }

    function getClientPropertyDefaultValue($clientPropertyID, $clientID) {
        // check related application property default value
        $appPropertyID = $this->db->getAppPropertyID_RelatedWith($clientPropertyID, $clientID);
        $appPropertyDefaultValue = null;
        if ($appPropertyID != '0') $appPropertyDefaultValue = $this->db->getAppPropertyDefaultValue($appPropertyID);
        if ($appPropertyDefaultValue != null) {
            // the client property is related and the application property default value (that has priority) exists, so return it
            return $appPropertyDefaultValue;
        } else {
            // the client property is not related or the application property default value is null, so return the client property default value
            return $this->db->getPropertyDefaultValue($clientPropertyID, $clientID);
        }
    }

    // Delete a client property from the db (all relationships also will be deleted)
    function deleteClientProperty($propertyID, $clientID) {
        global $propertiesTables;
        // get item type
        $itemTypeID = $this->getClientPropertyItemType($propertyID, $clientID);
        $propertyType = $this->db->getPropertyType($propertyID, $clientID);
        if ($this->db->RSDelete('properties_tables', 'ClientProperty', [$propertiesTables[$propertyType], $propertyID, $clientID]) 
         && ($propertyType == 'image' || $propertyType == 'file')) {
            deleteMediaProperty($clientID,$propertyID); //TODO: Update when RSMmediaManagement.php is refactored
        }
        $this->db->RSDelete('item_property', 'ClientProperty', [$propertyID, $clientID]);
        $this->db->RSDelete('property_app_relations', 'PropertyApp', [$propertyID, $clientID]);
        $this->db->RSDelete('properties_groups', 'ClientPropertyGroup', [$propertyID, $clientID]);
        $this->db->RSDelete('properties_lists', 'ClientPropertyList', [$propertyID, $clientID]);
        $this->db->RSDelete('token_permissions', 'ClientPropertyPermission', [$propertyID, $clientID]);
        if ($propertyID == getMainPropertyID($itemTypeID, $clientID)) {
            // reset main value
            $this->db->RSUpdate('item_types', 'MainProperty', [0, $itemTypeID, $clientID]);
        }
    }

    // Return the ID of the item type identified (property ID is the ID of an identifier client property)
    function getClientPropertyReferredItemType($propertyID, $clientID) {
        // prepare first query to get the user referred itemtype and execute the query
        $result = $this->db->getReferredItemType($propertyID, $clientID);
        if ($result && $result != '' && $result != '0') return $result;
    
        // prepare second query to get the system referred itemtype and execute the query
        $result = $this->db->getAppRelationsItemtype($propertyID, $clientID);
        if ($result) return $result;
        return 0;
    }

    // Return the ID of the item type identified (appPropertyName is the name of an identifier application property)
    function getClientPropertyReferredItemType_byName($appPropertyName, $clientID) {
        $propertyID = $this->db->getAppPropertyIDByName($appPropertyName);
        $appItemTypeID = $this->db->getAppPropertyReferredItemType($propertyID);
        return $this->db->getClientItemTypeID_RelatedWith($appItemTypeID, $clientID);
    }

}