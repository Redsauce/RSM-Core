<?php
trait TraitPropertyAppRelations {
    abstract public function connect();
    
    // Return the ID of the application's property related with the client property passed
    function getAppPropertyID_RelatedWith($clientPropertyID, $clientID) {
        global $db;
        $result = $db->RSSelect('property_app_relations', 'PropertyApp', [$clientPropertyID, $clientID]);
        if (!$result) return '0';
        $appPropertyID = $result;
        if ($appPropertyID)
            return $appPropertyID['RS_PROPERTY_APP_ID'];
        return '0';
    }
}