<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID           = $GLOBALS['RS_POST']["clientID"];
$allowedItemTypeIDs = $GLOBALS['RS_POST']["allowedItemTypeIDs"];

// Now we build the query
if (!isset($GLOBALS['RS_POST']['listAll'])) {
        $theQuery = "SELECT DISTINCT `rs_item_types`.`RS_ITEMTYPE_ID` as 'ID', `rs_item_types`.`RS_NAME` as 'NAME', `rs_item_types`.`RS_ORDER` FROM `rs_item_types` INNER JOIN (`rs_categories` INNER JOIN `rs_item_properties` ON `rs_item_properties`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` AND `rs_item_properties`.`RS_CATEGORY_ID`=`rs_categories`.`RS_CATEGORY_ID`) ON `rs_item_types`.`RS_CLIENT_ID`=`rs_categories`.`RS_CLIENT_ID` AND `rs_item_types`.`RS_ITEMTYPE_ID`=`rs_categories`.`RS_ITEMTYPE_ID` WHERE " . ($allowedItemTypeIDs == "" ? "" : "`rs_item_types`.`RS_ITEMTYPE_ID` IN (" . $allowedItemTypeIDs . ") AND ") . "`rs_item_types`.`RS_CLIENT_ID`='" . $clientID . "' AND `rs_item_properties`.`RS_PROPERTY_ID` IN (SELECT `rs_properties_groups`.`RS_PROPERTY_ID` FROM `rs_properties_groups` INNER JOIN (`rs_groups` INNER JOIN `rs_users_groups` ON `rs_groups`.`RS_GROUP_ID`=`rs_users_groups`.`RS_GROUP_ID` AND `rs_groups`.`RS_CLIENT_ID`=`rs_users_groups`.`RS_CLIENT_ID`) ON `rs_groups`.`RS_GROUP_ID`=`rs_properties_groups`.`RS_GROUP_ID` AND `rs_groups`.`RS_CLIENT_ID`=`rs_properties_groups`.`RS_CLIENT_ID` WHERE `rs_users_groups`.`RS_USER_ID`=" . $RSuserID . " AND `rs_users_groups`.`RS_CLIENT_ID`=" . $clientID . ") ORDER BY `rs_item_types`.`RS_ORDER`";
} else {
        $theQuery = "SELECT `RS_ITEMTYPE_ID` as 'ID', `RS_NAME` as 'NAME' FROM `rs_item_types` WHERE " . ($allowedItemTypeIDs == "" ? "" : "`RS_ITEMTYPE_ID` IN (" . $allowedItemTypeIDs . ") AND ") . "`RS_CLIENT_ID`='" . $clientID . "' ORDER BY `RS_ORDER`";
}

// Query the database
$results = RSquery($theQuery);

// And write XML Response back to the application
RSreturnQueryResults($results);
