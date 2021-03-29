<?php
trait TraitClientCategory {
    abstract public function connect();

    function getClientCategoryItemType($clientPropertyCategory, $clientID) {
        global $db;
        $result = $db->RSSelect('client_category', 'ItemType', [$clientPropertyCategory, $clientID]);
        if ($result) return $result['RS_ITEMTYPE_ID'];
        return '0';
    }

    function getClientPropertyCategory($clientPropertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_property', 'Category', [$clientPropertyID, $clientID]);
        if ($result) return $result['RS_CATEGORY_ID'];
        return '';
    }

    // Return the name of the category passed
    function getClientCategoryName($clientCategoryID, $clientID) {
        global $db;
        $result = $db->RSSelect('client_category', 'Name', [$clientCategoryID, $clientID]);
        if ($result) $result['RS_NAME'];
        return '';
    }

    // Return the list of properties of the category passed
    function getClientCategoryProperties($clientCategoryID, $clientID, $avoidDuplicateProperty = 0) {
        global $db;
        $queryConcatenation = '';
        if ($avoidDuplicateProperty <> 0) {
            $queryConcatenation = $queryConcatenation . " AND RS_AVOID_DUPLICATION = 0";
        }
        $queryConcatenation = $queryConcatenation . ' ORDER BY RS_ORDER';
        $result = $db->RSSelect('client_category', 'ItemProperties', [$clientCategoryID, $clientID], $queryConcatenation);
        $propertiesList = [];
        if ($result) {
            foreach ($result as $row) {
                $propertiesList[] = array('id' => $row['RS_PROPERTY_ID'], 'name' => $row['RS_NAME'], 'type' => $row['RS_TYPE']);
            }
        }
        return $propertiesList;
    }
}


