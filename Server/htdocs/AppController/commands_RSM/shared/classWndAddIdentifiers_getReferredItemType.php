<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS[$cstRS_POST]['clientID'];
$propertyID = $GLOBALS[$cstRS_POST]['propertyID'];

$results['itemTypeID'] = getClientItemTypeID_RelatedWith(getAppPropertyReferredItemType(getAppPropertyID_RelatedWith($propertyID, $clientID)), $clientID);

// And write XML Response back to the application
RSReturnArrayResults($results);
?>