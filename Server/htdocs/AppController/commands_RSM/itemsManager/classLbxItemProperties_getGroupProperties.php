<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if ($GLOBALS['RS_POST']['clientID'] != 0 && $GLOBALS['RS_POST']['groupID'] != 0 && $GLOBALS['RS_POST']['itemtypeID'] != 0) {
    //We check if the group exists into the client

    $theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='".$GLOBALS['RS_POST']['groupID']."' AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID'];
    $resultgroupOK = RSQuery($theQuery_groupValidation);

    //show query if debug mode
    if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
        echo $theQuery_groupValidation;
    }

    if ($resultgroupOK->num_rows != 0) {
        //The group exists, so perform the action
        $theQuery = "SELECT DISTINCT rs_properties_groups.RS_PROPERTY_ID as 'propertyID' FROM rs_categories INNER JOIN (rs_item_properties INNER JOIN (rs_groups INNER JOIN rs_properties_groups ON rs_groups.RS_GROUP_ID=rs_properties_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_properties_groups.RS_CLIENT_ID) ON rs_item_properties.RS_PROPERTY_ID=rs_properties_groups.RS_PROPERTY_ID AND rs_item_properties.RS_CLIENT_ID=rs_properties_groups.RS_CLIENT_ID) ON rs_categories.RS_CATEGORY_ID=rs_item_properties.RS_CATEGORY_ID AND rs_categories.RS_CLIENT_ID=rs_item_properties.RS_CLIENT_ID WHERE rs_properties_groups.RS_GROUP_ID = ".$GLOBALS['RS_POST']['groupID']." AND rs_groups.RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID']." AND rs_categories.RS_ITEMTYPE_ID=".$GLOBALS['RS_POST']['itemtypeID'];

        //show query if debug mode
        if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
            echo $theQuery;
        }
        // Query the database
        $results = RSQuery($theQuery);
        // And write XML Response back to the application
        RSReturnQueryResults($results);
        exit;
    } else {
        $results["result"] = "NOK";
        RSReturnArrayResults($results);
        exit;
    }
} else {
    $results["result"] = "NOK";
    RSReturnArrayResults($results);
    exit;
}
