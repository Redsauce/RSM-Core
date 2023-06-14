<?php
// ****************************************************************************************
//Description:
//    Retrieves items of the specified itemType with the associated values with filter conditions
//
//  --- PARAMETERS -- All the IDs can be called as systemNames --
//   RStoken      : A token can replace the clientID
//   clientID     : ID of the client, is not necessary if the token is passed
//   propertyIDs  : string with the IDs of the properties to retrieve: ID1,ID2, ... ,IDN
//   filterRules  : string with filter conditions: ID1;base64(value1);condition1,ID2;base64(value2);condition2 ... ,IDN;base64(valueN);conditionN
//   extFilterRules: string with the ID of the external property, the value in base64, and the condition: ID;base64(value);condition
//   filterJoining: could be AND or OR. By default = AND.
//    translateIDs: if this property is set to false then the IDs won't be tranlated
//                  by the main property value of the item the ID is pointing to
// ****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";
require_once "../utilities/RSMlistsManagement.php";
require_once "../utilities/RStools.php";
require_once "./api_headers.php";

$RSallowUncompressed = true;

// Definitions
isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyIDs"]) ? $pIDs = $GLOBALS["RS_POST"]["propertyIDs"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["filterRules"]) ? $filterRules = $GLOBALS["RS_POST"]["filterRules"] : $filterRules = "";
isset($GLOBALS["RS_POST"]["filterJoining"]) ? $filterJoining = $GLOBALS["RS_POST"]["filterJoining"] : $filterJoining = "AND";
isset($GLOBALS["RS_POST"]["extFilterRules"]) ? $extFilterRules = $GLOBALS["RS_POST"]["extFilterRules"] : $extFilterRules = "";
isset($GLOBALS["RS_POST"]["RStoken"]) ? $RStoken = $GLOBALS["RS_POST"]["RStoken"] : $RStoken = "";
isset($GLOBALS["RS_POST"]["IDs"]) ? $IDs = $GLOBALS['RS_POST']['IDs'] : $IDs = "";
isset($GLOBALS["RS_POST"]["orderBy"]) ? $orderBy = $GLOBALS['RS_POST']['orderBy'] : $orderBy = "";
isset($GLOBALS['RS_POST']['orderPropertyID']) ? $orderPropertyID = $GLOBALS['RS_POST']['orderPropertyID'] : $orderPropertyID = "";

// Don't allow empty properties to be specified
if (strpos($pIDs, ",,") !== false) {
    dieWithError(400);
}

$translateIDs = false;
if (isset($GLOBALS['RS_POST']['translateIDs']) && $GLOBALS['RS_POST']['translateIDs'] == "true") {
    $translateIDs = true;
}

// Construct filterProperties using a double explode
$rules = array();
$rule  = array();
$filterProperties  = array();
$filterPropertyIDs = array();

if ($filterRules != '') {
    $rules = explode(",", $filterRules);
    foreach ($rules as $ruleN) {
        $rule = explode(";", $ruleN);

        // Obtain the property value
        if (isBase64($rule[1])) {
            // The user is specifying a custom base64 filter value
            $pValue = str_replace("&amp;", "&", htmlentities(base64_decode($rule[1]), ENT_COMPAT, "UTF-8"));

            if (($rule[2] != "<-IN") && ($rule[2] != "IN")) {
                // Under <-IN and IN clausules, we need a list of values separated by '
                $pValue = str_replace("'", "&#39;", $pValue);
            }
        } else {
            // The value is not encoded in base64 so try to get a related property with the value
            $pValue = getValue(getClientListValueID_RelatedWith(getAppListValueID($rule[1]), $clientID), $clientID);
        }
        $filterProperties[] = array('ID' => parsePID($rule[0], $clientID), 'value' => $pValue, 'mode' => $rule[2]);
    }
}

$propertyIDs = explode(",", $pIDs);

//creck if need to get the order from a property and add to returned properties in that case
$returnOrder = 0;
if ($orderPropertyID != "") {
    $returnOrder = 1;
    if ($orderPropertyID != "0") {
        $propertyType = getPropertyType($orderPropertyID, $clientID);
        if (isSingleIdentifier($propertyType) || isMultiIdentifier($propertyType)) {
            if (!in_array($orderPropertyID, $propertyIDs)) {
                $propertyIDs[] = $orderPropertyID;
            }
        } else {
            $response['result'] = "NOK";
            $response['description'] = "ORDER PROPERTY MUST BE 0 (DEFAULT ORDER) OR A VALID IDENTIFIER(S) TYPE PROPERTY";
            RSReturnArrayResults($response, false);
        }
    }
}

if (empty($propertyIDs)) {

    // No properties are specified, so use the filterRules in order to guess the item type
    foreach ($filterProperties as $filterProperty) {
        $filterPropertyIDs[] = $filterProperty['ID'];
    }

    $itemTypeID = getItemTypeIDFromProperties($filterPropertyIDs, $clientID);
} else {
    $itemTypeID = getItemTypeIDFromProperties($propertyIDs, $clientID);
}

if ($itemTypeID <= 0) {
    $response['result'] = "NOK";
    $response['description'] = "PROPERTIES MUST PERTAIN TO THE SAME ITEM TYPE";
    RSReturnArrayResults($response, false);
}

// Check if user has permissions to read properties of the item
if (!RShasTokenPermissions($RStoken, $propertyIDs, "READ") && (!arePropertiesVisible($RSuserID, $propertyIDs, $clientID))) {
    $results['result'] = 'NOK';
    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO READ THESE ITEMS';
    RSReturnArrayResults($results, false);
}

// Construct returnProperties array
$returnProperties = array();

foreach ($propertyIDs as $property) {
    $returnProperties[] = array('ID' => ParsePID($property, $clientID), 'name' => $property, 'trName' => $property . 'trs');
}

//check and translate order by
if ($orderBy != '') {
    $pID = ParsePID($orderBy, $clientID);
    if ($pID != '') {
        $orderBy = $pID;
    }
}

// Filter results
$results = array();
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy, $translateIDs, $limit = '', $IDs, $filterJoining, $returnOrder, true, $extFilterRules, true);

// And write XML Response back to the application without compression// Return results
if (is_string($results)) {
    RSReturnFileResults($results, false);
} else {
    RSReturnArrayQueryResults($results, false);
}
