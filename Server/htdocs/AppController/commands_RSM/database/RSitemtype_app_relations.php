<?php
trait TraitItemtypeAppRelations {
    abstract public function connect();
    
    // Return the ID of the client item type related with the application item type passed
    function getClientItemTypeID_RelatedWith($appItemTypeID, $clientID) {
        global $db;
        $result = $db->RSSelect('item_type_app_relations', 'ClientAppItemtype', [$appItemTypeID, $clientID]);
        if ($result && $clientItemTypeID = $result) {
            return $clientItemTypeID['RS_ITEMTYPE_ID'];
        } else
            return '0';
    }
}