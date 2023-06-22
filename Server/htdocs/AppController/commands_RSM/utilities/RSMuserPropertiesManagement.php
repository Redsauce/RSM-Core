<?php

function getUserProperties($userID, $clientID, $itemTypeID)
{

    // build a fast query to get user properties
    $theQueryGetProperties = 'SELECT DISTINCT rs_categories.RS_NAME AS "categoryName", rs_categories.RS_ORDER, rs_item_properties.RS_PROPERTY_ID AS "propertyID", rs_item_properties.RS_NAME AS "propertyName", rs_item_properties.RS_TYPE AS "propertyType", rs_item_properties.RS_ORDER FROM rs_categories INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) INNER JOIN rs_properties_groups USING (RS_CLIENT_ID, RS_PROPERTY_ID) INNER JOIN rs_users_groups USING (RS_CLIENT_ID, RS_GROUP_ID) WHERE (rs_categories.RS_ITEMTYPE_ID = '.$itemTypeID.' AND rs_categories.RS_CLIENT_ID = '.$clientID.') AND (rs_item_properties.RS_CLIENT_ID = '.$clientID.') AND (rs_properties_groups.RS_CLIENT_ID = '.$clientID.') AND (rs_users_groups.RS_USER_ID = '.$userID.' AND rs_users_groups.RS_CLIENT_ID = '.$clientID.') ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER';
    // execute query
    $theProperties = RSquery($theQueryGetProperties);

    // get properties values
    $results = array();
    $properties = array();

    while ($row = $theProperties->fetch_assoc()) {
        // save the property ID
        $properties[] = $row['propertyID'];

        // store info
        $results[] = array(
            'propertyID'   => $row['propertyID'],
            'propertyName' => $row['propertyName'],
            'propertyType' => $row['propertyType'],
            'category'     => $row['categoryName']
        );
    }

    $results[] = array('lists' => '');

    if (!empty($properties)) {
        // build a fast query to get the properties lists
        $theQueryPropertiesList = 'SELECT rs_lists.RS_LIST_ID AS "listID", rs_property_values.RS_VALUE AS "listValue", rs_properties_lists.RS_PROPERTY_ID AS "propertyID", rs_properties_lists.RS_MULTIVALUES AS "multiValues" FROM rs_lists INNER JOIN rs_property_values USING (RS_CLIENT_ID, RS_LIST_ID) INNER JOIN rs_properties_lists USING (RS_CLIENT_ID, RS_LIST_ID) WHERE (rs_lists.RS_CLIENT_ID = '.$clientID.') AND (rs_property_values.RS_CLIENT_ID = '.$clientID.') AND (rs_properties_lists.RS_PROPERTY_ID IN ('.implode(',', $properties).') AND rs_properties_lists.RS_CLIENT_ID = '.$clientID.') ORDER BY rs_properties_lists.RS_PROPERTY_ID, rs_property_values.RS_ORDER';

        // execute query
        $theLists = RSQuery($theQueryPropertiesList);

        // store info
        while ($row = $theLists->fetch_assoc()) {
            $results[] = $row;
        }
    }

    return $results;
}
