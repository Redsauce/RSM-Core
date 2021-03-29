<?php

class Property {
    public Database $db;
    
    function __construct($db) {
        $this->db = $db;
    }
    
    function isIdentifier($propertyID, $clientID, $typeName = '') {
        if ($typeName == '')
            $typeName = $this->db->getPropertyType($propertyID, $clientID);
        
        return (($typeName == 'identifier') || ($typeName == 'identifiers') || ($typeName == 'identifier2itemtype') || ($typeName == 'identifier2property'));
    }
    
    // A simply function that returns true if the value passed (an identifier value) is a null value for identifiers, such as '0' or ''
    function isNullIdentifier($value) {
        return ($value == '0' || $value == '');
    }
    
    function isSingleIdentifier($propertyType) {
        return ($propertyType == 'identifier');
    }
    
    function isMultiIdentifier($propertyType) {
        return ($propertyType == 'identifiers');
    }
    
    function isIdentifier2itemtype($propertyType) {
        return ($propertyType == 'identifier2itemtype');
    }
    
    function isIdentifier2property($propertyType) {
        return ($propertyType == 'identifier2property');
    }
}