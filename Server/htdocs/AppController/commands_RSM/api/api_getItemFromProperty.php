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

header('Access-Control-Allow-Origin: *');

$RSallowUncompressed = true;

// Capture the variables needed for this script to work
isset($GLOBALS[$cstRS_POST]["clientID"        ]) ? $clientID         = $GLOBALS[$cstRS_POST]["clientID"        ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["itemType"        ]) ? $itemType         = $GLOBALS[$cstRS_POST]["itemType"        ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["filterProperty"  ]) ? $filterProperty   = $GLOBALS[$cstRS_POST]["filterProperty"  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]["filterPropertyID"]) ? $filterPropertyID = $GLOBALS[$cstRS_POST]["filterPropertyID"] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken          = $GLOBALS[$cstRS_POST][$cstRStoken] : $RStoken     = '';

$translateIDs = true;
if (isset($GLOBALS[$cstRS_POST]['translateIDs'])) {
	if ($GLOBALS[$cstRS_POST]['translateIDs'] == "true") $translateIDs = true;
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

if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $filterItemType, $filterPropertyID)) {
    $results['result'] = 'NOK';
    $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THE SOURCE ITEM';
    RSReturnArrayResults($results, false);
}

// get the properties of the itemType
$properties = getClientItemTypeProperties($itemType, $clientID);

//
if (strpos($valuePropertyRelated, ",") === false) {
	//single item identified, return it as usual for backwards compatibility
    if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemType, $valuePropertyRelated)) {
        $results['result'] = 'NOK';
        $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THIS ITEM';
        RSReturnArrayResults($results, false);
    }
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
	              'trs' => base64_encode(html_entity_decode(getMainPropertyValue(getClientPropertyReferredItemType($property['id'], $clientID), $value, $clientID)))
	              );
	
	        } elseif ($translateIDs && $property['type'] == 'identifiers') {
	            $IDs = explode(",", $value);
	            $trsProperties = '';
	            $relatedItemType = getClientPropertyReferredItemType($property['id'], $clientID);
	
	            foreach ($IDs as $id) {
	                $trsProperties .= base64_encode(html_entity_decode(getMainPropertyValue($relatedItemType, $value, $clientID))) . ",";
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
    $filterProperties = RSappendTokenCustomerScopeFilter($RStoken, $clientID, $itemType, array());
    if ($filterProperties === false) {
        $results['result'] = 'NOK';
        $results['description'] = 'TOKEN CUSTOMER SCOPE DOES NOT ALLOW ACCESS TO THIS ITEM TYPE';
        RSReturnArrayResults($results, false);
    }
	$results = getFilteredItemsIDs($itemType, $clientID, $filterProperties, $properties, '', $translateIDs, '', $valuePropertyRelated, 'AND', 0, true, '', true);
}

// And write XML Response back to the application without compression// Return results
if (is_string($results)) {
    RSReturnFileResults($results, false);
} else {
    RSReturnArrayQueryResults($results, false);
}
