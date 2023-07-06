<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];


// get concepts item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['concepts'], $clientID);

// get concepts properties IDs
$conceptNamePropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptName'], $clientID);
$conceptProjectPropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptProjectID'], $clientID);
$conceptUnitsPropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptUnits'], $clientID);
$conceptIVAPropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptIVA'], $clientID);
$conceptPricePropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptPrice'], $clientID);
$conceptDeductionPropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptDeduction'], $clientID);
$conceptStockItemPropertyID = getClientPropertyIDRelatedWithByName($definitions['conceptStockItemID'], $clientID);

// get concepts properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);



// --- FIRST PART RESULTS ---
// get properties names (they will be assigned to the list columns)
if (in_array($conceptNamePropertyID, $propertiesAllowed)) {
    $nameAllowed = '1';
} else {
    $nameAllowed = '0';
}
if (in_array($conceptProjectPropertyID, $propertiesAllowed)) {
    $projAllowed = '1';
} else {
    $projAllowed = '0';
}
if (in_array($conceptUnitsPropertyID, $propertiesAllowed)) {
    $unitsAllowed = '1';
} else {
    $unitsAllowed = '0';
}
if (in_array($conceptIVAPropertyID, $propertiesAllowed)) {
    $IVAAllowed = '1';
} else {
    $IVAAllowed = '0';
}
if (in_array($conceptPricePropertyID, $propertiesAllowed)) {
    $priceAllowed = '1';
} else {
    $priceAllowed = '0';
}
if (in_array($conceptDeductionPropertyID, $propertiesAllowed)) {
    $deductionAllowed = '1';
} else {
    $deductionAllowed = '0';
}
if (in_array($conceptStockItemPropertyID, $propertiesAllowed)) {
    $stockItemIDAllowed = '1';
} else {
    $stockItemIDAllowed = '0';
}


$results[0]['concepts'] = getClientItemTypeName($itemTypeID, $clientID);

$results[0]['name'] = getClientPropertyName($conceptNamePropertyID, $clientID) . '::' . $nameAllowed;  // fix me: separator used -> ::
$results[0]['project'] = getClientPropertyName($conceptProjectPropertyID, $clientID) . '::' . $projAllowed;
$results[0]['units'] = getClientPropertyName($conceptUnitsPropertyID, $clientID) . '::' . $unitsAllowed;
$results[0]['VAT'] = getClientPropertyName($conceptIVAPropertyID, $clientID) . '::' . $IVAAllowed;
$results[0]['price'] = getClientPropertyName($conceptPricePropertyID, $clientID) . '::' . $priceAllowed;
$results[0]['deduction'] = getClientPropertyName($conceptDeductionPropertyID, $clientID) . '::' . $deductionAllowed;
$results[0]['stockItemID'] = getClientPropertyName($conceptStockItemPropertyID, $clientID) . '::' . $stockItemIDAllowed;

// get properties default values
$results[0]['nameDefValue'] = getClientPropertyDefaultValue($conceptNamePropertyID, $clientID);
$results[0]['projectIDDefValue'] = getClientPropertyDefaultValue($conceptProjectPropertyID, $clientID);
$results[0]['unitsDefValue'] = getClientPropertyDefaultValue($conceptUnitsPropertyID, $clientID);
$results[0]['VATDefValue'] = getClientPropertyDefaultValue($conceptIVAPropertyID, $clientID);
$results[0]['priceDefValue'] = getClientPropertyDefaultValue($conceptPricePropertyID, $clientID);
$results[0]['deductionDefValue'] = getClientPropertyDefaultValue($conceptDeductionPropertyID, $clientID);
$results[0]['projectDefValue'] = translateSingleIdentifier($conceptProjectPropertyID, $results[0]['projectIDDefValue'], $clientID);
$results[0]['stockItemDefValue'] = getClientPropertyDefaultValue($conceptStockItemPropertyID, $clientID);

// get operations and projects item types
$operationsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);
$projectsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['projects'], $clientID);
$subAccountsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['subAccounts'], $clientID);

// get operation subAccountID
if ($subAccountID == "") {
    $subAccountID = getPropertyValue($definitions['operationSubAccountID'], $operationsItemTypeID, $operationID, $clientID);
}
// get operation subAccountName
$results[0]['subAccountName'] = getMainPropertyValue($subAccountsItemTypeID, $subAccountID, $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['projectClient'], $clientID), 'value' => $subAccountID, 'mode' => 'IN');

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getMainPropertyID($projectsItemTypeID, $clientID), 'name' => 'mainValue');

// get projects
$projectsQueryResults = iqGetFilteredItemsIDs($projectsItemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

if ($projectsQueryResults->num_rows == 0) {
    // If there were no results returned, we will list all the open projects
    $openStatus = getValue(getClientListValueIDRelatedWith(getAppListValueID('projectStatusOpen'), $clientID), $clientID);
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['projectStatus'], $clientID), 'value' => $openStatus);
    $projectsQueryResults = iqGetFilteredItemsIDs($projectsItemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');
}

// --- SECOND PART RESULTS ---
while ($row = $projectsQueryResults->fetch_assoc()) {
    $results[] = $row;
}

// And return XML results
RSreturnArrayQueryResults($results);
