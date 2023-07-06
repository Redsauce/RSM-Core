<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";


// --- DEFINITIONS ---
// -------------------
isset($GLOBALS['RS_POST']['clientID']) ? $clientID        =              $GLOBALS['RS_POST']['clientID']  : dieWithError(400);
// this variable can be set to 0, indicating that the operations will not be filtered by accountID; it can be an accountID or a list of accounts IDs
isset($GLOBALS['RS_POST']['accountID']) ? $accountID       =              $GLOBALS['RS_POST']['accountID']  : dieWithError(400);
// this variable can be set to 0, indicating that the operations will not be filtered by subAccountID; it can be an subAccountID or a list of subAccount IDs
isset($GLOBALS['RS_POST']['subAccountID']) ? $subAccountID    =              $GLOBALS['RS_POST']['subAccountID']  : dieWithError(400);
// this variable can be set to 0, indicating that the operations will not be filtered by linkOperationID
isset($GLOBALS['RS_POST']['linkOperationID']) ? $linkOperationID =              $GLOBALS['RS_POST']['linkOperationID']  : dieWithError(400);
// this variable can be set to 0, indicating that the operations will not be filtered by year; otherwise, this value must be formed by an year and a property, separated by semicolon (for example, 2009;SendDate)
isset($GLOBALS['RS_POST']['year']) ? $year            =              $GLOBALS['RS_POST']['year']  : dieWithError(400);
// this variable contains the names of the properties you want to return, separated by coma; the property names are contained in the RSdefinitions file; this string must be contain only the second part of those names (without the item type definition)
isset($GLOBALS['RS_POST']['propertyList']) ? $propertyNames   = explode(',', $GLOBALS['RS_POST']['propertyList']) : dieWithError(400);
isset($GLOBALS['RS_POST']['filterList']) ? $filterList      =              $GLOBALS['RS_POST']['filterList']  : $filterList = '';

if ($subAccountID != '0') {
    // we will filter by subAccountID only...
    $accountID = '0';
}

// build an associative array for the properties (name => ID)
foreach ($propertyNames as $propertyName) {
    // add entry to the properties list
    $propertiesList[$propertyName] = getClientPropertyIDRelatedWithByName($definitions['operation' . $propertyName], $clientID);
}



// --- GET OPERATIONS ---
// ----------------------

// get operations item type
$itemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);


// build filter properties array
$filterProperties = array();

if ($accountID != '0') {
    // get subaccounts item type
    $subAccountsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['subAccounts'], $clientID);

    // build filter properties array
    $saFilterProperties = array();
    if (strpos($accountID, ',') === false) {
        $saFilterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID);
    } else {
        $saFilterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['subAccountAccountID'], $clientID), 'value' => $accountID, 'mode' => '<-IN');
    }

    $subAccountsQueryResults = iqGetFilteredItemsIDs($subAccountsItemTypeID, $clientID, $saFilterProperties, array());

    $subAccounts = array();

    if ($subAccountsQueryResults) {
        while ($row = $subAccountsQueryResults->fetch_assoc()) {
            $subAccounts[] = $row['ID'];
        }
    }

    // filter operations by accountID
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID), 'value' => implode(',', $subAccounts), 'mode' => '<-IN');
}

if ($subAccountID != '0') {
    // filter operations by subAccountID
    if (strpos($subAccountID, ',') === false) {
        $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID), 'value' => $subAccountID);
    } else {
        $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID), 'value' => $subAccountID, 'mode' => '<-IN');
    }
}

if ($linkOperationID != '0') {
    // filter operations by linkOperationID
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID), 'value' => $linkOperationID, 'mode' => 'IN');
}

if ($year != '0') {
    // get year and property
    $yearArr = explode(';', $year);

    // get the property ID
    $yearFilterPropertyID = getClientPropertyIDRelatedWithByName($definitions['operation' . $yearArr[1]], $clientID);

    // filter operations by year
    $filterProperties[] = array('ID' => $yearFilterPropertyID, 'value' => ($yearArr[0] - 1) . '-12-31', 'mode' => 'AFTER');
    $filterProperties[] = array('ID' => $yearFilterPropertyID, 'value' => ($yearArr[0] + 1) . '-01-01', 'mode' => 'BEFORE');
}

//check filter parameter sent
if ($filterList != "") {
    $filterArray = split(",", $filterList);
    foreach ($filterArray as $filterElement) {
        $auxFilter = explode("=", $filterElement);

        if (count($auxFilter) == 3) {
            $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operation' . $auxFilter[0]], $clientID), 'value' => $auxFilter[1], 'mode' => $auxFilter[2]);
        } else {
            $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operation' . $auxFilter[0]], $clientID), 'value' => $auxFilter[1]);
        }
    }
}


// build return properties array
$returnProperties = array();

foreach ($propertyNames as $propertyName) {
    // add the property to return
    $returnProperties[] = array('ID' => $propertiesList[$propertyName], 'name' => $propertyName);
}


// get operations
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);


// Write XML Response back to the application
RSreturnArrayQueryResults($results);
