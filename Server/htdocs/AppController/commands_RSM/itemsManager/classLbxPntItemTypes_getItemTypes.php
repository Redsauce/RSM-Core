<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$referredItemType = $GLOBALS['RS_POST']['referredItemType'];

// prepare query
$theQuery = 'SELECT rs_item_types.RS_ITEMTYPE_ID AS "ID", rs_item_types.RS_NAME AS "name", rs_item_properties.RS_PROPERTY_ID AS "propertyID" FROM rs_item_types INNER JOIN rs_categories USING (RS_CLIENT_ID, RS_ITEMTYPE_ID) INNER JOIN rs_item_properties USING (RS_CLIENT_ID, RS_CATEGORY_ID) WHERE RS_CLIENT_ID = '.$clientID.' AND RS_TYPE IN ("identifier","identifiers") AND RS_REFERRED_ITEMTYPE = '.$referredItemType.' ORDER BY RS_ITEMTYPE_ID';

// execute query
$results = RSQuery($theQuery);

// Return results
RSReturnQueryResults($results);
?>
