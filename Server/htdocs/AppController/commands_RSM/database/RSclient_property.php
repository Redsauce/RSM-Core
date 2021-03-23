<?php
trait TraitClientProperty { //TODO: Revisar el nombre

    abstract public function connect();

    public function getPropertyType($propertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_property', 'Type', [parsePID($propertyID, $clientID), $clientID]);
        if ($result && $propertyRes = $result)
            return $propertyRes['RS_TYPE'];

        $db->RSError("RSMitemsManagement: getPropertyType: property not found: ".$propertyID);
        return '';
    }

    public function getPropertyDefaultValue($clientPropertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_property', 'DefaultValue', [$clientPropertyID, $clientID]);
        if (!$result) return '';
        $propertyDefaultValue = $result;
        return $propertyDefaultValue['RS_DEFAULTVALUE'];
    }

    // Return the name of the property passed
    function getClientPropertyName($clientPropertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_property', 'Name', [parsePID($clientPropertyID, $clientID), $clientID]);
        if ($result && $propertyName = $result) {
            return $propertyName['RS_NAME'];
        } else {
            return '';
        }
    }

    function getReferredItemType($propertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_property', 'ReferredItemtype', [$propertyID, $clientID]);
        if ($result && $row = $result) return $row['RS_REFERRED_ITEMTYPE'];
        return '';
    }

    function getAppRelationsItemtype($propertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_type_app_relations', 'ClientPropertyItemtype', [$propertyID, $clientID, $clientID]);
        if ($result && $row = $result) return $row['itemTypeID'];
        return '';
    }
}