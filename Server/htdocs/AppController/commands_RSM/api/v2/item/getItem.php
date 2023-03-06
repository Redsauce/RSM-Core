<?php
//*****************************************************************************
//Description:
//    Retrieves an item of the specified itemType with the associated values
//
//  PARAMETERS:
//  itemTypeID: ID of the itemType to retrieve
//  itemID: ID of the item to retrieve
//*****************************************************************************
require_once "../../utilities/RSdatabase.php";
require_once "../../utilities/RSMitemsManagement.php";
require_once "../api_headers.php";

function getItem($itemTypeID, $itemID, $RStoken){
    //We get the clientID from THE RStoken (Authorization)
    $clientID   = RSClientFromToken($RStoken);

    //Read all properties and iterate through them
    $properties = getClientItemTypeProperties($itemTypeID, $clientID);
    foreach ($properties as $property) {
        // Check if user has read permission of the property
        if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
           
            $value = getItemDataPropertyValue($itemID, $property['id'], $clientID);

            if (($property['type'] == 'image') || ($property['type'] == 'file')) {
                // A file needs additional properties like the file name and the file size, so let's query the database for extra attributes
                $attributes = explode(":", getItemPropertyValue($itemID, $property['id'], $clientID));
                $results[] = array(
                'ID' => $property['id'],
                'related' => getAppPropertyName_RelatedWith($property['id'], $clientID),
                'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => $value, 'type' => $property['type'],
                'filename' => array_key_exists(0,$attributes)?$attributes[0]:'',
                'filesize' => array_key_exists(1,$attributes)?$attributes[1]:''
                );

            } elseif ($translateIDs && $property['type'] == 'identifier') {
                $results[] = array(
                'ID' => $property['id'],
                'related' => getAppPropertyName_RelatedWith($property['id'], $clientID),
                'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => $value,
                'type' => $property['type'],
                'trs' => base64_encode(getMainPropertyValue(getClientPropertyReferredItemType($property['id'], $clientID), $value, $clientID))
                );

            } elseif ($translateIDs && $property['type'] == 'identifiers') {
                $IDs = explode(",", $value);
                $trsProperties = '';
                $relatedItemType = getClientPropertyReferredItemType($property['id'], $clientID);

                foreach ($IDs as $id) {
                    $trsProperties .= base64_encode(getMainPropertyValue($relatedItemType, $id, $clientID)) . ",";
                }

                $results[] = array(
                'ID' => $property['id'],
                'related' => getAppPropertyName_RelatedWith($property['id'], $clientID),
                'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => $value,
                'type' => $property['type'],
                'trs' => rtrim($trsProperties, ",")
                );

            } else {
                $results[] = array(
                'ID' => $property['id'],
                'related' => getAppPropertyName_RelatedWith($property['id'], $clientID),
                'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
                'value' => html_entity_decode($value, ENT_COMPAT|ENT_QUOTES, "UTF-8"),
                'type' => $property['type']);
            }
        }
    }
    
    // Write JSON Response back to the application
    RSReturnJsonQueryResults($results);
}
?>
