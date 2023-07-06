<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMbankCodes.php";

// definitions
$clientID     = $GLOBALS['RS_POST']['clientID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];

// get the item types
$itemTypeID           = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);
$accountsItemTypeID   = getClientItemTypeID_RelatedWith_byName($definitions['accounts'], $clientID);
$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);
$conceptsItemTypeID   = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

// get the subaccount accountID
$accountID = getPropertyValue($definitions['subAccountAccountID'], $itemTypeID, $subAccountID, $clientID);

// get the account type
if (getPropertyValue($definitions['accountType'], $accountsItemTypeID, $accountID, $clientID) == '430') {
    // delete associated users
    $usersItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['users'], $clientID);

    // build filter properties array
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['userSubAccountID'], $clientID), 'value' => $subAccountID);

    $useRSqueryResults = IQ_getFilteredItemsIDs($usersItemTypeID, $clientID, $filterProperties, array());

    $users = array();
    while ($row = $useRSqueryResults->fetch_assoc()) {
        $users[] = $row['ID'];
    }

    if (!empty($users)) {
        // delete concepts
        deleteItems($usersItemTypeID, $clientID, implode(',', $users));
    }

    // also delete the relationships between the user and the modules
    RSquery('DELETE FROM rs_extranet_modules_users WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEM_ID IN (' . implode(',', $users) . ')');
}


// get subaccount operations
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'], $clientID), 'value' => $subAccountID);

$operationsQueryResults = IQ_getFilteredItemsIDs($operationsItemTypeID, $clientID, $filterProperties, array());

// get operations "related operations" property ID
$relatedOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);

$operations = array();
while ($row = $operationsQueryResults->fetch_assoc()) {
    $operations[] = $row['ID'];

    // get the operation related operations
    $filterProperties = array();
    $filterProperties[] = array('ID' => $relatedOperationsPropertyID, 'value' => $row['ID'], 'mode' => 'IN');

    $relatedOperations = IQ_getFilteredItemsIDs($operationsItemTypeID, $clientID, $filterProperties, array());

    // delete the relationships
    while ($operation = $relatedOperations->fetch_assoc()) {
        removeIdentifier($row['ID'], $operationsItemTypeID, $operation['ID'], $relatedOperationsPropertyID, $clientID, $RSuserID);
    }
}

if (!empty($operations)) {
    // get operations concepts
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID), 'value' => implode(',', $operations), 'mode' => '<-IN');

    $conceptsQueryResults = IQ_getFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, array());

    $concepts = array();
    while ($row = $conceptsQueryResults->fetch_assoc()) {
        $concepts[] = $row['ID'];
    }

    if (!empty($concepts)) {
        // delete concepts
        deleteItems($conceptsItemTypeID, $clientID, implode(',', $concepts));
    }

    // delete operations
    deleteItems($operationsItemTypeID, $clientID, implode(',', $operations));
}

// finally delete the subaccount
deleteItem($itemTypeID, $subAccountID, $clientID);


$results['result'] = 'OK';

// Return results
RSreturnArrayResults($results);
