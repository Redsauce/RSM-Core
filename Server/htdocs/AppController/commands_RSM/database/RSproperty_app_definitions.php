<?php
trait TraitPropertyAppDefinitions {
    abstract public function connect();
    
    // Return the default value of the application property passed
    function getAppPropertyDefaultValue($appPropertyID) {
        global $db;
        $result = $db->RSSelect('property_app_definitions', 'DefaultValue', [$appPropertyID]);
        if (!$result) return "";
        $appPropertyDefaultValue = $result;
        return $appPropertyDefaultValue['RS_DEFAULTVALUE'];
    }

    // Return the item type identified by the application property passed
    function getAppPropertyReferredItemType($appPropertyID) {
        global $db;
        $result = $db->RSSelect('property_app_definitions', 'ReferredItemtype', [$appPropertyID]);
        if (!$result) return "";
        $appPropertyItemTypeID = $result;
        return $appPropertyItemTypeID['RS_REFERRED_ITEMTYPE'];
    }

    // Return the ID of the property $appPropertyName
    function getAppPropertyIDByName($appPropertyName) {
        global $db;
        $result = $db->RSSelect('property_app_definitions', 'IdByName', [$appPropertyName]);
        if ($result && $appPropertyID = $result) return $appPropertyID['RS_ID'];
        return '0';
    }
}