<?php
//****************************************************************************************
//Description:
//    Retrieves an item of the specified itemType with the associated values
//
//  PARAMETERS:
//  itemType: itemType to retrieve (for example: the itemType of crm-accounts)
//  filterProperty: property of another itemType related with the first one (for example: the property 'client' into invoices)
//  filterPropertyID: itemID of the filter property (for example: The identifier of the invoice from which we get the client)
//****************************************************************************************
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

// Capture the variables needed for this script to work
isset($GLOBALS["RS_POST"]["clientID"        ]) ? $clientID         = $GLOBALS["RS_POST"]["clientID"        ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemType"        ]) ? $itemType         = $GLOBALS["RS_POST"]["itemType"        ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["filterProperty"  ]) ? $filterProperty   = $GLOBALS["RS_POST"]["filterProperty"  ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["filterPropertyID"]) ? $filterPropertyID = $GLOBALS["RS_POST"]["filterPropertyID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["RStoken"         ]) ? $RStoken          = $GLOBALS["RS_POST"]["RStoken"         ] : $RStoken     = '';

$translateIDs = true;
if (isset($GLOBALS['RS_POST']['translateIDs'])) {
      if ($GLOBALS['RS_POST']['translateIDs'] == "true") $translateIDs = true;
}

$properties   = array();
$attributes   = array();
$results      = array();

// get type of the filterProperty
$propertyType = getPropertyType($filterProperty, $clientID);

// if filterProperty is unsupported type return empty response
if (!isSingleIdentifier($propertyType) && !isMultiIdentifier($propertyType)){
    RSReturnArrayQueryResults($results, false);
}
        
// Get itemType of the filter property
$filterItemType = getItemTypeIDFromProperties(array($filterProperty), $clientID);

// Get the value of the property $filterPropertyID for the given $filterProperty
$valuePropertyRelated = getItemPropertyValue($filterPropertyID, $filterProperty, $clientID);

// get the properties of the itemType
$properties = getClientItemTypeProperties($itemType, $clientID);

//
if (strpos($valuePropertyRelated, ",") === false) {
	//single item identified, return it as usual for backwards compatibility
	foreach ($properties as $property) {
	    // Check if user has read permission of the property
	    if ((RShasTokenPermission($RStoken, $property['id'], "READ")) || (isPropertyVisible($RSuserID, $property['id'], $clientID))) {
	        $value = getItemDataPropertyValue($valuePropertyRelated, $property['id'], $clientID);
	
	        if (($property['type'] == 'image') || ($property['type'] == 'file')) {
	            // A file needs additional properties like the file name and the file size, so let's query the database for extra attributes
	            $attributes = explode(":", getItemPropertyValue($valuePropertyRelated, $property['id'], $clientID));
	
	            $results[] = array(
	              'ID' => $property['id'],
	              'name' => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
	              'value' => $value,
	              'type' => $property['type'],
	              'filename' => array_key_exists(0,$attributes)?$attributes[0]:'',
	              'filesize' => array_key_exists(1,$attributes)?$attributes[1]:''
	              );
	
	        } elseif ($translateIDs && $property['type'] == 'identifier') {
	            $results[] = array(
	              'ID' => $property['id'],
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
	                $trsProperties .= base64_encode(getMainPropertyValue($relatedItemType, $value, $clientID)) . ",";
	            }
	
	            $results[] = array(
	              'ID'    => $property['id'],
	              'name'  => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
	              'value' => $value,
	              'type'  => $property['type'],
	              'trs'   => rtrim($trsProperties, ",")
	            );
	
	        } else {
	            $results[] = array(
	              'ID'    => $property['id'],
	              'name'  => html_entity_decode($property['name'], ENT_COMPAT, "UTF-8"),
	              'value' => html_entity_decode($value, ENT_COMPAT|ENT_QUOTES, "UTF-8"),
	              'type'  => $property['type']);
	
	        }
	    }
	}

} else {
	//multiple items, use getFilteredItemsIDs to return all
	
	// Check if user has permissions to read properties of the item and remove otherwise
	foreach($properties as $key => $property)
	{
	    // fix the id vs ID key issue TODO: review all code and solve it
	    $properties[$key]['ID'] = $property['id'];
		$properties[$key]['name'] = html_entity_decode($property['name'], ENT_COMPAT, "UTF-8");
	    if (!RShasTokenPermission($RStoken, $property['id'], "READ") && (!isPropertyVisible($RSuserID, $property['id'], $clientID))) {
	    	unset($properties[$key]);
		}
	}
	
	//check at least one property allowed and exit otherwise
	if (count($properties) == 0) {
	    $results['result'] = 'NOK';
	    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO READ THESE ITEMS';
	    RSReturnArrayResults($results, false);
	}

	// get the items
	$results = getFilteredItemsIDs($itemType, $clientID, array(), $properties, '', $translateIDs, '', $valuePropertyRelated, 'AND', 0, true, '', true);
}

// And write XML Response back to the application without compression// Return results
if (is_string($results)) {
    RSReturnFileResults($results, false);
} else {
    RSReturnArrayQueryResults($results, false);
}
?>
