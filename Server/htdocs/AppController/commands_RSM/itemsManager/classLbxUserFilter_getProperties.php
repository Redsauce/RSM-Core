<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if (($GLOBALS['RS_POST']['clientID'] != 0) && ($GLOBALS['RS_POST']['userID'] != 0) && ($GLOBALS['RS_POST']['itemtypeID'] != 0)) {

    $theQuery="SELECT DISTINCT rs_item_properties.RS_PROPERTY_ID, rs_item_properties.RS_NAME, rs_item_properties.RS_TYPE, rs_categories.RS_NAME as catName FROM rs_categories INNER JOIN (rs_item_properties INNER JOIN rs_properties_groups ON rs_properties_groups.RS_PROPERTY_ID=rs_item_properties.RS_PROPERTY_ID AND rs_properties_groups.RS_CLIENT_ID=rs_item_properties.RS_CLIENT_ID) ON rs_item_properties.RS_CATEGORY_ID=rs_categories.RS_CATEGORY_ID AND rs_item_properties.RS_CLIENT_ID=rs_categories.RS_CLIENT_ID WHERE rs_item_properties.RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID']." AND rs_categories.RS_ITEMTYPE_ID=". $GLOBALS['RS_POST']['itemtypeID']." AND rs_properties_groups.RS_GROUP_ID IN (SELECT rs_users_groups.RS_GROUP_ID FROM rs_users_groups INNER JOIN rs_groups ON rs_users_groups.RS_GROUP_ID=rs_groups.RS_GROUP_ID AND rs_users_groups.RS_CLIENT_ID=rs_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_USER_ID = " . $GLOBALS['RS_POST']['userID'] . " AND rs_users_groups.RS_CLIENT_ID=". $GLOBALS['RS_POST']['clientID'].") ORDER BY rs_categories.RS_ORDER, rs_item_properties.RS_ORDER";

    $theProperties = RSQuery($theQuery);

    // Show query if debug mode
    if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
        echo $theQuery;
    }

    while ($theProperty = $theProperties->fetch_array()) {
        $data[] = array("propertyID"=>$theProperty['RS_PROPERTY_ID'], "category"=>$theProperty['catName'], "propertyName"=>$theProperty['RS_NAME'], "propertyType"=>$theProperty['RS_TYPE']);
    }
} else {
    $data["result"] = "NOK";
}
// And write XML Response back to the application
RSReturnArrayQueryResults($data);
