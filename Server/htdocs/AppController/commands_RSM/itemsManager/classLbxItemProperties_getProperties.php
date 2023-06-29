<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID   = $GLOBALS['RS_POST']['clientID'  ];
$itemTypeID = $GLOBALS['RS_POST']['itemtypeID'];

if ($clientID != 0 && $itemTypeID != 0) {

    $data = array();

    // get categories
    $categoriesList = getClientItemTypeCategories($itemTypeID, $clientID);

    foreach ($categoriesList as $category) {

        // get properties info
        $propertiesList = RSQuery('SELECT a.RS_PROPERTY_ID AS "id", a.RS_NAME AS "name", a.RS_TYPE AS "type", a.RS_REFERRED_ITEMTYPE AS "referredItemType", a.RS_ORDER, IF(b.RS_PROPERTY_APP_ID IS NULL,0,1) AS "related" FROM rs_item_properties a LEFT JOIN rs_property_app_relations b USING (RS_CLIENT_ID, RS_PROPERTY_ID) WHERE a.RS_CATEGORY_ID = ' . $category['id'] . ' AND a.RS_CLIENT_ID = ' . $clientID . ' ORDER BY a.RS_ORDER');
        
        if ($propertiesList) {
            while ($row = $propertiesList->fetch_assoc()) {
                // add category name to the array
                $row['category'] = $category['name'];

                // append to the data array
                $data[] = $row;
            }
        }
    }

    // Write XML response back to application
    RSReturnArrayQueryResults($data);
} else {
    $data["result"] = "NOK";

    // Write XML response back to application
    RSReturnArrayResults($data);
}
