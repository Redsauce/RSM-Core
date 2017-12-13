<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$itemTypeID = $GLOBALS['RS_POST']['itemTypeID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

$categoriesList = getClientItemTypeCategories($itemTypeID, $clientID);

$results = array();
// get properties info
foreach ($categoriesList as $category) {
	$propertiesList = getClientCategoryProperties($category['id'], $clientID);
	foreach ($propertiesList as $property) {
		// store info
		$results[] = $property;
	}
}
	
// Return results			
RSReturnArrayQueryResults($results);
?>