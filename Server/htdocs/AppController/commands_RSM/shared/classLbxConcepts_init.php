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
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

// get concepts properties IDs
$conceptNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptName'], $clientID);
$conceptProjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptProjectID'], $clientID);
$conceptUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptUnits'], $clientID);
$conceptIVAPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptIVA'], $clientID);
$conceptPricePropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptPrice'], $clientID);
$conceptDeductionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptDeduction'], $clientID);
$conceptStockItemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptStockItemID'], $clientID);

// get concepts properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);



// --- FIRST PART RESULTS ---
// get properties names (they will be assigned to the list columns)
if (in_array($conceptNamePropertyID, $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }
if (in_array($conceptProjectPropertyID, $propertiesAllowed)) { $projAllowed = '1'; } else { $projAllowed = '0'; }
if (in_array($conceptUnitsPropertyID, $propertiesAllowed)) { $unitsAllowed = '1'; } else { $unitsAllowed = '0'; }
if (in_array($conceptIVAPropertyID, $propertiesAllowed)) { $IVAAllowed = '1'; } else { $IVAAllowed = '0'; }
if (in_array($conceptPricePropertyID, $propertiesAllowed)) { $priceAllowed = '1'; } else { $priceAllowed = '0'; }
if (in_array($conceptDeductionPropertyID, $propertiesAllowed)) { $deductionAllowed = '1'; } else { $deductionAllowed = '0'; }
if (in_array($conceptStockItemPropertyID, $propertiesAllowed)) { $stockItemIDAllowed = '1'; } else { $stockItemIDAllowed = '0'; }


$results[0]['concepts'] = getClientItemTypeName($itemTypeID, $clientID);

$results[0]['name'			] = getClientPropertyName($conceptNamePropertyID		, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::
$results[0]['project'		] = getClientPropertyName($conceptProjectPropertyID		, $clientID).'::'.$projAllowed;
$results[0]['units'			] = getClientPropertyName($conceptUnitsPropertyID		, $clientID).'::'.$unitsAllowed;
$results[0]['VAT'			] = getClientPropertyName($conceptIVAPropertyID			, $clientID).'::'.$IVAAllowed;
$results[0]['price'			] = getClientPropertyName($conceptPricePropertyID		, $clientID).'::'.$priceAllowed;
$results[0]['deduction'		] = getClientPropertyName($conceptDeductionPropertyID	, $clientID).'::'.$deductionAllowed;
$results[0]['stockItemID'	] = getClientPropertyName($conceptStockItemPropertyID	, $clientID).'::'.$stockItemIDAllowed;

// get properties default values
$results[0]['nameDefValue'		] = getClientPropertyDefaultValue($conceptNamePropertyID		, $clientID);
$results[0]['projectIDDefValue'	] = getClientPropertyDefaultValue($conceptProjectPropertyID		, $clientID);
$results[0]['unitsDefValue'		] = getClientPropertyDefaultValue($conceptUnitsPropertyID		, $clientID);
$results[0]['VATDefValue'		] = getClientPropertyDefaultValue($conceptIVAPropertyID			, $clientID);
$results[0]['priceDefValue'		] = getClientPropertyDefaultValue($conceptPricePropertyID		, $clientID);
$results[0]['deductionDefValue'	] = getClientPropertyDefaultValue($conceptDeductionPropertyID	, $clientID);
$results[0]['projectDefValue'	] = translateSingleIdentifier    ($conceptProjectPropertyID, $results[0]['projectIDDefValue'], $clientID);
$results[0]['stockItemDefValue'	] = getClientPropertyDefaultValue($conceptStockItemPropertyID	, $clientID);

// get operations and projects item types
$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);
$projectsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['projects'], $clientID);
$subAccountsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

// get operation subAccountID
if($subAccountID==""){
	$subAccountID = getPropertyValue($definitions['operationSubAccountID'], $operationsItemTypeID, $operationID, $clientID);
}
// get operation subAccountName
$results[0]['subAccountName'] = getMainPropertyValue($subAccountsItemTypeID, $subAccountID, $clientID);

// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['projectClient'], $clientID), 'value' => $subAccountID, 'mode' => 'IN');

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getMainPropertyID($projectsItemTypeID, $clientID), 'name' => 'mainValue');

// get projects
$projectsQueryResults = IQ_getFilteredItemsIDs($projectsItemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');

if ($projectsQueryResults->num_rows == 0) {
	// If there were no results returned, we will list all the open projects
	$openStatus = getValue(getClientListValueID_RelatedWith(getAppListValueID('projectStatusOpen'), $clientID), $clientID);
	$filterProperties = array();
	$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['projectStatus'], $clientID), 'value' => $openStatus);
	$projectsQueryResults = IQ_getFilteredItemsIDs($projectsItemTypeID, $clientID, $filterProperties, $returnProperties, 'mainValue');
}

// --- SECOND PART RESULTS ---
while ($row = $projectsQueryResults->fetch_assoc()) {
	$results[] = $row;
}

// And return XML results
RSReturnArrayQueryResults($results);
?>
