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
//  extFilterRules: string with the ID of the external property, the value in base64, and the condition: ID;base64(value);condition
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
isset($GLOBALS["RS_POST"]["clientID"       ]) ? $clientID        = $GLOBALS["RS_POST"]["clientID"       ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemTypeID"     ]) ? $itemTypeID      = $GLOBALS["RS_POST"]["itemTypeID"     ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["filterRules"    ]) ? $filterRules     = $GLOBALS["RS_POST"]["filterRules"    ] : $filterRules     = "";
isset($GLOBALS["RS_POST"]["filterJoining"  ]) ? $filterJoining   = $GLOBALS["RS_POST"]["filterJoining"  ] : $filterJoining   = "AND";
isset($GLOBALS["RS_POST"]["extFilterRules" ]) ? $extFilterRules  = $GLOBALS["RS_POST"]["extFilterRules" ] : $extFilterRules  = "";
isset($GLOBALS["RS_POST"]["RStoken"        ]) ? $RStoken         = $GLOBALS["RS_POST"]["RStoken"        ] : $RStoken         = "";

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
        if (is_base64($rule[1])) {
            // The user is specifying a custom base64 filter value
            $pValue = str_replace("&amp;", "&", htmlentities(base64_decode($rule[1]), ENT_COMPAT, "UTF-8"));
        } else {
            // The value is not encoded in base64 so try to get a related property with the value
            $pValue = getValue(getClientListValueID_RelatedWith(getAppListValueID($rule[1]), $clientID), $clientID);
        }
        $filterProperties[] = array('ID' => parsePID($rule[0], $clientID), 'value' => $pValue, 'mode' => $rule[2]);
    }
} else {
    $filterProperties = NULL;
}
$itemTypeID = parseITID($itemTypeID,$clientID);
if ($itemTypeID <= 0) {
    $response['result'] = "NOK";
    $response['description'] = "INVALID ITEM TYPE";
    RSReturnArrayResults($response, false);
}

// Check if user has permissions to apply the filters on the item
$filterPropertyIDs=array();
foreach ($filterProperties as $filterProperty) {
    $filterPropertyIDs[] = $filterProperty['ID'];
}

if (!RShasTokenPermissions($RStoken, $filterPropertyIDs, "READ")) {
    $results['result'] = 'NOK';
    $results['description'] = 'YOU DONT HAVE PERMISSIONS TO READ THESE ITEMS';
    RSReturnArrayResults($results, false);
}

// Construct returnProperties array
$returnProperties = array();

// Filter results
$results = array();
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', false, '', '', $filterJoining, 0, false, $extFilterRules, true);

$result = array("total" => count($results));

// And write XML Response back to the application without compression
RSReturnArrayResults($result, false);
?>
